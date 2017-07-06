# API PRouDS
Untuk tahap sekarang api yang tersedia : 
* LOGIN controller
    * Login
    * Logout
    * Register User/Vendor
    
* HOME controller
    * Detail Project overview
    * Project Team And Member
    * Project Docs and Files
    * Project Issue
    
    
    

    


## LOGIN CONTROLLER

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
-> userdata <= informasi mengenai user
-> datatimesheet <= persentase hasil dari timesheet untuk bulan ini
-> project <= daftar Project user
-> bussines_unit <= String nama bisnis unit dari user tersebut
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

# HOME CONTROLLER

## Detail project
Detail project overview bisa di capai dengan ke url :
```
http://45.77.45.126/dev/detaiproject/<id dari project>
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
http://45.77.45.126/dev/p_teammember/<id dari project>
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
http://45.77.45.126/dev/projectdoc/<id dari project>
```
Return data json object yang di terima :
```
-> userdata
-> project_doc_list <= berisi array informasi setiap doc untuk project ybs
```
### Upload Project Doc/Files
URI yang di gunakan :
```
http://45.77.45.126/dev/documentupload/<id dari project>
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
http://45.77.45.126/dev/projectissue/<id dari project>
```
Return data json object yang di terima
```
-> userdata
-> project_issue_list <= berisi array tentang informasi issue
```

### Upload project issue
URI yang digunakan 
```
http://45.77.45.126/dev/addissue
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