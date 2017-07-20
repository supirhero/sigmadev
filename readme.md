# API PRouDS
Untuk tahap sekarang api yang tersedia : 
* LOGIN controller
    * Login
    * Logout
    * Register User/Vendor
    * Refresh Token
    
* HOME controller
    * Detail Project overview
    * Project Team And Member
    * Project Docs and Files
    * Project Issue
    * My Activity
    * My Assignment
    * Project Activity
    
* REPORT controller
    * My Performances
    * My Activity
    
    
## LOGIN CONTROLLER


### IMPORTANT !!
Login sudah memakai sistem token , mohon provide token untuk setiap request ke 
api dengan cara menyediakan token di , token bisa di dapatkan ketika sudah
melakukan login. Token akan expired ketika sudah berumur 2 jam terhitung 
waktu generate.
```
-> Token bisa di dapati ketika proses login berhasil
-> Untuk mengakses api setelah login, token di sisipi di header 
      Dengan nama 'token'
```

jika token tidak bisa di gunakan/ expired , maka return json error :
```
->login_error (isi pesan error token)
```
### Login 
Menggunakan HTTP method POST,URI ajax :

```
http://45.77.45.126/dev/login/login
```


Input yang harus di provide :

```
user_id  <= id user, menggunakan email
password <= password,user
fpid     <= 160927084946 <= nilai ini dari source code asli
```


Jika user berhasil Login, maka API akan me-return data user, data timesheet(untuk bulan ini) dan data project user dalam bentuk json string. 

Data yang ada di dalam json string :
```
-> token
-> userdata <= informasi mengenai user
-> datatimesheet <= persentase hasil dari timesheet untuk bulan ini
-> project <= daftar Project user
    {}-> bu_name
        -> project_list
```

Jika user tidak berhasil Login, akan ada error message yang akan di return dalam bentuk json string 
```
-> error <- keterangan error dari user, err1 untuk salah user , err2 untuk salah password
-> title = 'error'
-> message = 'username atau password tidak cocok'
```
## Logout
Destroy session user data, URI logout :
```
http://45.77.45.126/dev/login/logout
```

## Register
Register terbagi 2, User dan Vendor. Untuk membedakan antara user dan vendor, di beri 1 variable POST patokan yaitu $_POST['Submit'] dimana :
  * $_POST['submit'] = 'registVendor' <- untuk vendor
  * $_POST['submit'] = 'registSigma'  <- untuk user sigma
### Vendor
Menggunakan HTTP method POST,URI ajax :

```
http://45.77.45.126/dev/login/doRegistration
```

Input yang harus di provide :
```
-> submit = 'registVendor' <- dikarnakan registrasi vendor
-> V_EMAIL_SUP
-> V_EMAIL
-> V_USER_ID
-> V_USER_NAME
-> V_PASSWORD
```
### User
Menggunakan HTTP method POST,URI ajax :

```
http://45.77.45.126/dev/login/doRegistration
```

Header HTTP method POST :
```
-> submit = 'registSigma' <- dikarnakan registrasi vendor
-> USER_ID => NIK
-> EMAIL
-> USERNAME
-> PASSWORD
```

## Refresh Token
Untuk merefresh token, di capai dengan ke URI :
```
http://45.77.45.126/dev/login/refreshtoken
```
Input yang harus di provide (POST):
```
-> token 
```
Return yang akan di terima jika sukses :
```
-> token <= token baru
```
Jika refresh token error
```
-> refresh_error <= pesan error
```

# HOME CONTROLLER

## Detail project
Detail project overview bisa di capai dengan ke url :
```
http://45.77.45.126/dev/home/detaiproject/<id dari project>
```
Return data json object yang di terima adalah :
```
-> userdata
-> overview
-> project_workplan_status
-> project_performance_index
-> project_team
```

## Project Team Member
URI yang di gunakan untuk akses API ini :
```
http://45.77.45.126/dev/home/p_teammember/<id dari project>
```

Return data json object yang di terima adalah :
```
-> userdata
-> project_member <= berisi array keterangan setiap team member
```

## Project Docs and files
### View Docs and Files list
URI yang di gunakan untuk akses API ini :
```
http://45.77.45.126/dev/home/projectdoc/<id dari project>
```
Return data json object yang di terima :
```
-> userdata
-> project_doc_list <= berisi array informasi setiap doc untuk project ybs
```
### Upload Project Doc/Files
URI yang di gunakan :
```
http://45.77.45.126/dev/home/documentupload/<id dari project>
```

Input yang harus di provide :
```
-> document(file input)
-> desc <= deskripsi tentang dokumen
```
Return data json object yang di terima
```
-> title <= 'success' untuk berhasil, 'error' untuk gagal
-> message <= isi keterangan pesan
```

## Project Issue
### View project issue list
URI yang di gunakan :
```
http://45.77.45.126/dev/home/projectissue/<id dari project>
```
Return data json object yang di terima
```
-> userdata
-> project_issue_list <= berisi array tentang informasi issue
```

### Upload project issue
URI yang digunakan 
```
http://45.77.45.126/dev/home/addissue
```

Input yang harus di provide :
```
-> PROJECT_ID
-> SUBJECT
-> MESSAGE
-> PRIORITY
-> file_upload(file input)
```
Return data json object yang di terima :
 ```
 -> title 
 -> message
 ```
 
 ## My Activity
 Daftar 20 aktivitas terakhir user, URI :
 ```
  http://45.77.45.126/dev/home/myactivities
 ```
 
 Return data json object :
 ```
 -> activity_timesheet
 ```
 
 
 ## Timesheet
 Menampilkan timesheet pada tanggal di parameter dan daftar hari kerja dalam minggu itu  :
 ```
  http://45.77.45.126/dev/home/timesheet/<tanggal(defaultnya hari ini)> 
 ```
 
 Return data json object :
 ```
 -> weekday
 -> activity_timesheet
 ```
 
 
### Add new timesheet
URI yang digunakan 
```
http://45.77.45.126/dev/home/addtimesheet
```

Input yang harus di provide :
```
-> TS_DATE
-> SUBJECT
-> MESSAGE
-> HOUR_TOTAL

```
Return data json object yang di terima :
 ```
 -> title 
 -> message
 ```
 
 ## My Assignment
 Daftar assignment user, URI :
 ```
  http://45.77.45.126/dev/home/myassignment
 ```
 
 Return data json object :
 ```
 -> activity_timesheet
 ```
 ## Project Activity
 Daftar aktifitas semua user yang berkerja di project bersangkutan. URI :
  ```
  http://45.77.45.126/dev/home/projectactivities/<id project>
  ```
  Return json object :
   ```
   -> project_activities <= Jika belum ada aktifitas ,maka kosong) 
   ```
 
 # REPORT CONTROLLER
 ## My Performances
 Performance user berdasarkan bulan dan tahun, URI yang di gunakan :
 ```
 http://45.77.45.126/dev/report/myperformances
 ```
Input yang harus di provide :
 ```
  ->bulan (ex : 1,2,3...11,12)
  ->tahun (ex : 2017)
 ```


Return json object yang di terima :
```
-> entry
-> utilization
-> status Utilization
-> status
-> allentry
-> allhour
```

## Directorat / BU
Sebelum mendapatkan data report Directorat / BU, user di minta untuk memilih 
Direktorat / bu yang akan di akses datanya, maka di perlukan API untuk mendapatkan 
data list direktorat/bu. URI API untuk mendapatkan list data direktorat / BU :
```
http://45.77.45.126/dev/report/r_list_bu
```
Setelah mendapatkan list dari direktorat / bu ,maka API report directorat / bu baru bisa berjalan.
URI untuk mengakses API ini :
```
http://45.77.45.126/dev/report/r_directoratbu
```
Input yang harus di provide :
```
->bu (ID Bussiness unit)
->tahun (tahun report untuk bussiness unit)
```
Json Return data :
```
-> project
    ->completed
    ->in_progress
    ->not_started
    ->jumlah
->finance
    ->total_project_value
```
