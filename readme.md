# API PRouDS
Untuk tahap sekarang api yang tersedia : 
* LOGIN controller
    * Login
    * Logout
    * Register User/Vendor
    


##Login 
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

## Logout
Destroy session user data, URI logout :
```
http://45.77.45.126/dev/login/logout
```

##Register
Register terbagi 2, User dan Vendor. Untuk membedakan antara user dan vendor, di beri 1 variable POST patokan yaitu $_POST['Submit'] dimana :
  * $_POST['submit'] = 'registVendor' <- untuk vendor
  * $_POST['submit'] = 'registSigma'  <- untuk user sigma
###Vendor
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
###User
Menggunakan HTTP method POST,URI ajax :

```
http://45.77.45.126/dev/login/doRegistration
```

Header HTTP method POST :
```
-> submit = 'registSigma' <- dikarnakan registrasi vendor
-> USER_ID
-> EMAIL
-> VENDOR
-> USERNAME
-> PASSWORD
```
