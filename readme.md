# API PRouDS
Untuk tahap sekarang api yang tersedia : 
* LOGIN controller
    * Login
    * Logout
    * Register User/Vendor
    
    
    

    


## LOGIN CONTROLLER

### Login 
Menggunakan HTTP method POST,URI ajax :

```
http://45.77.45.126/dev/login/login
```


header POST yang di perlukan untuk login :

```
user_id  <= id user, menggunakan email
password <= password,user
fpid     <= 160927084946 <= nilai ini dari source code asli
```


Jika user berhasil Login, maka API akan me-return data user, data timesheet(untuk bulan ini) dan data agenda user dalam bentuk json string. 

Data yang ada di dalam json string :
```
-> userdata <= informasi mengenai user
-> datatimesheet <= persentase hasil dari timesheet untuk bulan ini
-> task_user <= daftar agenda user
-> bussines_unit <= Nama bisnis unit dari user tersebut
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

Header HTTP method POST :
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

