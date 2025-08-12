cmake_minimum_required(VERSION 3.16)
project(he_bfv_cli LANGUAGES CXX)
set(CMAKE_CXX_STANDARD 17)
set(CMAKE_CXX_STANDARD_REQUIRED ON)

find_package(SEAL REQUIRED) # pastikan Microsoft SEAL terpasang & terdeteksi

add_executable(he_bfv main.cpp)
# Target bisa SEAL::seal atau seal tergantung paket
if(TARGET SEAL::seal)
  target_link_libraries(he_bfv PRIVATE SEAL::seal)
else()
  target_link_libraries(he_bfv PRIVATE seal)
endif()
