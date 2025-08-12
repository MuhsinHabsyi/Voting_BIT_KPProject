#include <seal/seal.h>
#include <iostream>
#include <fstream>
#include <vector>
#include <string>
#include <stdexcept>

/* --- util base64 kecil (encode/decode) --- */
static const char b64tab[] =
  "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
std::string b64encode(const std::vector<unsigned char>& in){
  std::string out; size_t i=0; unsigned val=0; int valb=-6;
  for(unsigned char c: in){ val=(val<<8)+c; valb+=8;
    while(valb>=0){ out.push_back(b64tab[(val>>valb)&0x3F]); valb-=6; } }
  if(valb>-6) out.push_back(b64tab[((val<<8)>>(valb+8))&0x3F]);
  while(out.size()%4) out.push_back('=');
  return out;
}
std::vector<unsigned char> b64decode(const std::string& s){
  std::vector<int> T(256,-1); for(int i=0;i<64;i++) T[(int)b64tab[i]]=i;
  std::vector<unsigned char> out; int val=0,valb=-8;
  for(unsigned char c: s){ if(T[c]==-1) { if(c=='\n' || c=='\r' || c==' ') continue; else break; }
    val=(val<<6)+T[c]; valb+=6; if(valb>=0){ out.push_back((unsigned char)((val>>valb)&0xFF)); valb-=8; } }
  return out;
}

/* --- path helpers --- */
std::string pjoin(const std::string& a, const std::string& b){
#ifdef _WIN32
  const char sep='\\';
#else
  const char sep='/';
#endif
  if(a.empty()) return b;
  if(a.back()==sep) return a+b;
  return a+sep+b;
}

/* --- load/save helpers --- */
void save_vec(const std::string& path, const std::stringstream& ss){
  std::ofstream f(path, std::ios::binary); f<<ss.rdbuf(); f.close();
}
std::stringstream load_ss(const std::string& path){
  std::ifstream f(path, std::ios::binary);
  if(!f) throw std::runtime_error("file not found: "+path);
  std::stringstream ss; ss<<f.rdbuf(); return ss;
}

/* --- BFV constants --- */
static constexpr size_t CAND_SLOTS = 5;

struct Ctx {
  seal::EncryptionParameters parms{seal::scheme_type::bfv};
  std::shared_ptr<seal::SEALContext> ctx;
  seal::PublicKey pk;
  seal::SecretKey sk;
  std::unique_ptr<seal::BatchEncoder> encoder;

  static std::string parms_path(const std::string& dir){ return pjoin(dir,"parms.seal"); }
  static std::string pub_path(const std::string& dir){ return pjoin(dir,"public.seal"); }
  static std::string sec_path(const std::string& dir){ return pjoin(dir,"secret.seal"); }

  static void keygen(const std::string& dir){
    // BFV params
    size_t poly_deg = 4096;
    seal::EncryptionParameters parms(seal::scheme_type::bfv);
    parms.set_poly_modulus_degree(poly_deg);
    parms.set_coeff_modulus(seal::CoeffModulus::BFVDefault(poly_deg));
    parms.set_plain_modulus(seal::PlainModulus::Batching(poly_deg, 20)); // t ~ 1,048,576

    auto context = std::make_shared<seal::SEALContext>(parms, true, seal::sec_level_type::tc128);
    seal::KeyGenerator keygen(*context);
    seal::PublicKey pk; keygen.create_public_key(pk);
    const seal::SecretKey& sk = keygen.secret_key();

    std::stringstream s_parms, s_pub, s_sec;
    parms.save(s_parms);
    pk.save(s_pub);
    sk.save(s_sec);
    save_vec(parms_path(dir), s_parms);
    save_vec(pub_path(dir), s_pub);
    save_vec(sec_path(dir), s_sec);
    std::cout << "OK" << std::endl;
  }

  void load(const std::string& dir, bool need_pub, bool need_sec){
    auto s_parms = load_ss(parms_path(dir));
    parms.load(s_parms);
    ctx = std::make_shared<seal::SEALContext>(parms, true, seal::sec_level_type::tc128);
    encoder = std::make_unique<seal::BatchEncoder>(*ctx);
    if(need_pub){ auto s=load_ss(pub_path(dir)); pk.load(*ctx, s); }
    if(need_sec){ auto s=load_ss(sec_path(dir)); sk.load(*ctx, s); }
  }
};

int cmd_keygen(const std::string& dir){
  Ctx::keygen(dir);
  return 0;
}

int cmd_encrypt(const std::string& dir, int cand){
  if(cand < 1 || cand > (int)CAND_SLOTS) { std::cerr<<"cand must be 1..5\n"; return 2; }
  Ctx c; c.load(dir, true, false);

  // one-hot 5 slot
  std::vector<uint64_t> slots(c.encoder->slot_count(), 0);
  slots[(size_t)(cand-1)] = 1;

  seal::Plaintext pt; c.encoder->encode(slots, pt);
  seal::Encryptor enc(*c.ctx, c.pk);
  seal::Ciphertext ct; enc.encrypt(pt, ct);

  std::stringstream ss; ct.save(ss);
  auto s = ss.str();
  std::vector<unsigned char> buf(s.begin(), s.end());
  std::cout << b64encode(buf) << std::endl;
  return 0;
}

int cmd_decrypt(const std::string& dir){
  Ctx c; c.load(dir, false, true);
  // read base64 ciphertext from stdin
  std::string b64, line;
  while(std::getline(std::cin, line)) { if(!line.empty()) { b64 = line; break; } }
  if(b64.empty()){ std::cerr<<"empty input\n"; return 2; }
  auto buf = b64decode(b64);
  std::stringstream ss; ss.write((const char*)buf.data(), (std::streamsize)buf.size());
  seal::Ciphertext ct; ct.load(*c.ctx, ss);

  seal::Decryptor dec(*c.ctx, c.sk);
  seal::Plaintext pt; dec.decrypt(ct, pt);
  std::vector<uint64_t> out; c.encoder->decode(pt, out);

  // ambil index nilai maksimum (1..5)
  size_t best=0; uint64_t bestv=0;
  for(size_t i=0; i<CAND_SLOTS; ++i){
    if(out[i] > bestv){ bestv = out[i]; best = i; }
  }
  std::cout << (int)(best+1) << std::endl;
  return 0;
}

int main(int argc, char** argv){
  try{
    if(argc < 3){
      std::cerr<<"usage:\n  he_bfv <keydir> keygen\n  he_bfv <keydir> encrypt <cand1..5>\n  he_bfv <keydir> decrypt < <b64cipher>\n";
      return 1;
    }
    std::string keydir = argv[1];
    std::string cmd = argv[2];
    if(cmd=="keygen") return cmd_keygen(keydir);
    if(cmd=="encrypt"){
      if(argc<4){ std::cerr<<"missing cand\n"; return 1; }
      return cmd_encrypt(keydir, std::stoi(argv[3]));
    }
    if(cmd=="decrypt") return cmd_decrypt(keydir);
    std::cerr<<"unknown cmd\n"; return 1;
  } catch(const std::exception& e){
    std::cerr<<"ERR: "<<e.what()<<"\n"; return 99;
  }
}
