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
    
* Project Controller
    * Add Project View Data
        * Check If IWO Already Used
        * Get Account Manager Based On IWO
        * Check Customer Based On IWO
        * Get Type Of Effort
    * Edit Project View
        
* Task Controller 
    * Create Task
    * Workplan View
    * Edit Task View
    * Edit Task Percent
    * Assign Task Member View
    * Add Task Member
    * Delete Task Member

* Timesheet Controller
    * Add Timesheet View
        * Get Task List
        * Get Total Approved Hours 
    * Add Timesheet Action
    * Approve Timesheet
    
    
## LOGIN CONTROLLER


### IMPORTANT !!
Sekarang token expired dalam 1 minggu dan tidak perlu lagi ada request/refresh token baru
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
URI untuk mengakses api ini :

```
http://45.77.45.126/dev/login/doRegistration
```

Input yang harus di provide :
```
-> submit = 'registVendor' <- dikarnakan registrasi vendor
-> V_EMAIL_SUP => email supervisor
-> V_EMAIL
-> V_USER_ID
-> V_USER_NAME
-> V_PASSWORD
```
### User
Alur login ,setelah user login ,user akan di kirim email verifikasi, untuk login internal(user), user harus terdaftar di API SSO .
Sehingga setelah login, user bisa menggunakan email untuk login ,tetapi jika tidak ,user memakai user_id untuk login

URI untuk mengakses api ini :
```
http://45.77.45.126/dev/login/doRegistration
```

 Input yang harus di provide :
```
-> submit = 'registSigma' <- dikarnakan registrasi vendor
-> USER_ID => NIK
-> EMAIL => harus mengunakan akhiran @sigma.co.id
-> USERNAME
-> PASSWORD
```

Return Json data :
```
-> title <= success/error
-> message
```


# HOME CONTROLLER

## Detail project
Detail project overview bisa di capai dengan ke url :
```
http://45.77.45.126/dev/home/detailproject/<id dari project>
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

## Project Docs anEdit Project Viewd files
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
 Daftar 1 minggu aktifitas terakhir user, URI :
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

# Project Controller
## Add Project View Data
API ini akan collect data yang berguna untuk mengisi data yang di perlukan form untuk submit project baru. 
URI untuk mengakses API ini :
```
http://45.77.45.126/dev/project/addProject_view/<Business Unit ID>
```
Return JSON data :Edit Project View
```
-> business_unit
-> IWO <= Array , fetching semua iwo
-> project_manager
```

Jika ingin memilih project status, value yang di tetapkan untuk project status adalah :
```
-> Not Started 
-> In Progress 
-> On Hold 
-> Completed 
-> Proposed  
-> In Planning 
-> Cancelled
```
Juga value untuk visibility :
```
-> (empty) untuk project member only
-> 1 untuk semua orang di business unit
-> 2 untuk global
```
Value untuk Type Of Expense
```
-> Capital Expense
-> Current Expense
-> Dedctible Expense
```

Ketika user mengisEdit Project Viewi form, ada beberapa validasi input yang harus di lakukan, yaitu :
* Check If IWO Already Used
* Get Account Manager Based On IWO
* Check Customer Based On IWO

Dikarnakan itu di sediakan API untuk fungsi tersebut , berikut api nya.
### Verify If IWO Already Used
URI untuk mengakses API ini :
```
http://45.77.45.126/dev/project/checkiwoused/
```
Input yang harus di provide (POST):
```
-> IWO_NO
```
Return JSON data :
```
-> jumlah <= 0 berarti belum digunakan
```
### Get Account Manager Based On IWO
URI untuk mengakses API ini :
```
http://45.77.45.126/dev/project/checkAM/
```
Input yang harus di provide (POST):
```
-> AM_ID <= ACCOUNT_MANAGER_ID , didapatkan di data IWO
```
Return JSON data :
```
-> username <= null berarti tidak ada nama
```
### Check Customer Based On IWO
URI untuk mengakses API ini :
```
http://45.77.45.126/dev/project/checkCustomer/
```
Input yang harus di provide (POST):
```
-> CUST_ID <= Customer ID , didapatkan di data IWO
```
Return JSON data :
```
-> customer_name <= null berarti tidak ada customer name
```
### Get Type Of Effort
Jika  telah memilih project type ,untuk mendapatkan project TYPE OF EFFORT adalah :
```
http://45.77.45.126/dev/project/checkProjectType/
```
input yang harus di provide :
```
-> PROJECT_TYPE_ID (Project atau Non Project)
```
Return Json Object :
```
-> type_of_effort  <= daftar type of efford
```


## Add Project Action
URI untuk mengakses API ini :
```
http://45.77.45.126/dev/project/addProject_acion/
```
Input yang harus di provide :
```
-> IWO_NO                          <= nomor IWO
-> PROJECT_NAME                    <= Didapati dari get Kode IWO
-> BU                              <= kode business unit( Didapati dari get kode IWO)
-> RELATED                         <= Related Business unit (Didapati dari get kode IWO)
-> CUST_ID                         <= id customer (Didapati dari get kode IWO)
-> END_CUST_ID                     <= ID End Customer (Didapati dari get IWO)
-> AMOUNT                          <= Project Value (Didapati dari get kode IWO)
-> MARGIN                          <= Didapati dari get kode IWO
-> DESC                            <= Project Description
-> PROJECT_TYPE_ID                 <= Tipe project ('Project' or 'Non Project')
-> PM                              <= Project Manager ID (Didapati dari geti IWO)
-> AM_ID                           <= ID Account manager (Didapati dari get IWO)
-> TYPE_OF_EFFORT                  <= Didapati dari API
-> PRODUCT_TYPE
-> PROJECT_STATUS
-> START                           <= start date create project
-> END                             <= planing untuk akhir project
-> VISIBILITY                      <= Berdasarkan pengaturan atas
-> TYPE_OF_EXPENSE                 <= Berdasarkan pengaturan di atas
-> OVERHEAD                        <= Project OVerhead
-> ACTUAL_COST
-> COGS
```
Jika Tidak ada nomor IWO, maka input yang seharusnya didapati dari nomor iwo harus di input manual.

Return json data :
```
-> status (success / error)
-> message
```

## Edit Project View
URI untuk mengakses API ini :
```
http://45.77.45.126/dev/project/editProject_view/<ID Project>
```
Return json data :
```
-> project_setting
-> project_business_unit_detail
-> available_project_type
-> IWO_list
-> project_manajer_list
-> account_manager_list
```
jika membutuhkan API Check If IWO Already Used, Get Account Manager Based On IWO ,Check Customer Based On IWO , Get Type Of Effort, bisa di dapatkan menggunakan API yang tersedia di atas

## Edit Project Action
URI untuk mengakses API ini :
```
http://45.77.45.126/dev/project/editProject_action/
```
Input yang harus di provide :
```
-> IWO_NO                          <= nomor IWO
-> PROJECT_NAME                    <= Didapati dari get Kode IWO
-> BU                              <= kode business unit( Didapati dari get kode IWO)
-> RELATED                         <= Related Business unit (Didapati dari get kode IWO)
-> CUST_ID                         <= id customer (Didapati dari get kode IWO)
-> END_CUST_ID                     <= ID End Customer (Didapati dari get IWO)
-> AMOUNT                          <= Project Value (Didapati dari get kode IWO)
-> MARGIN                          <= Didapati dari get kode IWO
-> DESC                            <= Project Description
-> PROJECT_TYPE_ID                 <= Tipe project ('Project' or 'Non Project')
-> PM                              <= Project Manager ID (Didapati dari geti IWO)
-> AM_ID                           <= ID Account manager (Didapati dari get IWO)
-> TYPE_OF_EFFORT                  <= Didapati dari API
-> PRODUCT_TYPE
-> PROJECT_STATUS
-> START                           <= start date create project
-> END                             <= planing untuk akhir project
-> VISIBILITY                      <= Berdasarkan pengaturan atas
-> TYPE_OF_EXPENSE                 <= Berdasarkan pengaturan di atas
-> OVERHEAD                        <= Project OVerhead
-> ACTUAL_COST
-> COGS
```

Return json data :
```
-> status (success / error)
-> message
```


# Task Controller
## Create Task
URI untuk mengakses API ini :
```
http://45.77.45.126/dev/task/checkCustomer/
```
Input yang harus di provide :
```
-> PROJECT_ID
-> WBS_NAME
-> WBS_ID
-> WBS_PARENT_ID
-> START_DATE <= ex :2017-07-25
-> FINISH_DATE <= ex :2017-07-25
```

Return data json jika proses berhasil/gagal :
```
-> status
```

## Workplan View
URI untuk mengakses API ini :
```
http://45.77.45.126/dev/task/workplan_view/<id_project>
```
Return data json :
```
-> tampil_detail
    -> array() <= semua data wbs berdasarkan project
```

## Edit Task View 
URI untuk mengakses API ini :
```
http://45.77.45.126/dev/task/edittask_view/<wbs_id>
```
Return data json :
```
-> hasil
    -> wbs_id
    -> wbs_parent_id
    -> project_id
    -> .. (semua data dari task bersangkutan)
```

## Edit Task Percent
URI untuk mengakses API ini :
```
http://45.77.45.126/dev/task/editTaskPercent/
```
Input yang harus di provide :
```
-> PROJECT_ID
-> WBS_ID
-> WORK_PERCENT_COMPLETE
-> START_DATE <= ex :2017-07-25
-> FINISH_DATE <= ex :2017-07-25
```

Return data json jika proses berhasil/gagal :
```
-> status
```

## Assign Task Member View
URI untuk mengakses API ini :
```
http://45.77.45.126/dev/task/assignTaskMember_view/
```
Input yang harus di provide :
```
-> PROJECT_ID
-> WBS_ID
```
Return data json :
```
-> task_name
-> available_to_assign
-> currently_assigned
```

## Add Task Member
URI untuk mengakses API ini :
```
http://45.77.45.126/dev/task/assignTaskMemberProject/
```
Input yang harus di provide :
```
-> WBS_ID (ID Task)
-> MEMBER (RP_ID)
-> EMAIL
-> NAME (Nama Anggota yang di delete)
-> WBS_NAME (Nama Task)
-> 
```
Return data json :
```
-> status
```

## Delete Task Member
URI untuk mengakses API ini :
```
http://45.77.45.126/dev/task/removeTaskMemberProject/
```
Input yang harus di provide :
```
-> WBS_ID
-> MEMBER (RP_ID)
-> EMAIL
-> NAME (Nama Anggota yang di delete)
-> WBS_NAME (Nama Task)
```
Return data json :
```
-> status
```

#TIMESHEET CONTROLLER
##  Add Timesheet View

URI untuk mengakses API ini :
```
http://45.77.45.126/dev/timesheet/view/
```
Input yang harus di provide :
```
->date <= tanggal untuk cek activity (ex 2017-07-31)
```
Return data json :
```
-> user_project (daftar project user)
    -> ...
    -> PROJECT_ID (Digunakan untuk Akses API task list)
    
-> user_activities (Aktifitas user berdasarkan waktu yang dipilih)
    -> ...
    -> is_approved (status timesheet, 1 = diterima, 0 = di tolak, -1 belum di konfirmasi)
    
-> holidays (daftar hari libur)
```
Untuk mendapatkan Daftar Task, maka di butuhkan Akses ke API dengan URI :
```
http://45.77.45.126/dev/timesheet/taskList/
```
dengan input :
 ```
 -> PROJECT_ID (didapati dari API sebelumnya)
 ```
Return JSON data :
 ```
 -> task 
    -> WP_ID (nanti di cantumkan ketika menambah timesheet)
    -> TASK_NAME
 ```
Untuk mendapatkan Daftar total jam kerja yang sudah di approve, di butuhkan akses ke API dengan URI:
```
http://45.77.45.126/dev/timesheet/allTaskHourTotal/
```
dengan input :
 ```
 -> date_start (tanggal mulai kalkulasi jam)
 -> date_end (tanggal akhir kalkulasi jam)
 ```
Return JSON data :
 ```
 -> hours 
    -> HOUR (jumlah jam semua timesheet di tanggal itu)
    -> TS_DATE (Tanggal timesheet)
    
 -> total_hours (Total semua jam task dari tanggal yang di cantum) 
 ``` 


## Add Timesheet Action
URI untuk mengakses API ini :
```
http://45.77.45.126/dev/timesheet/addTimesheet/
```
Input yang harus di provide :
```
-> WP_ID (DIDAPATI KETIKA VIEW TIMESHEET)
-> TS_DATE (ex 2017-07-31)
-> HOUR
-> TS_SUBJECT 
-> TS_MESSAGE
-> LATITUDE
-> LONGTITUDE
```
Return data json :
```
-> status
```

## Approve Timesheet
URI untuk mengakses API ini :
```
http://45.77.45.126/dev/timesheet/confirmationTimesheet/
```

Input yang harus di provide :
```
-> ts_id (id timesheet , di dapati ketika akses API)
-> confirm (1 untuk approve , 0 untuk deny)
```