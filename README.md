BACKUPER
===========================

Backuper adalah aplikasi PHP untuk melakukan backup database dan file pada hosting ke berbagai media penyimpanan.
Backuper berbasis CLI agar mudah digunakan dalam cronjob untuk menjalankan backup secara rutin.

Backuper dibuat menggunakan library [`flysystem`](https://flysystem.thephpleague.com),
jadi kamu dapat menyimpan file backup ke berbagai media penyimpanan
seperti local, hosting/server lain (via ftp/sftp), dropbox, google drive, onedrive, Amazon S3, dsb.

Backuper dibuat atas dasar kebutuhan pada shared hosting, jadi untuk saat ini
backuper hanya dapat melakukan backup pada database MySQL/MariaDB.

## Requirement

* PHP >= 5.5.9
* Ekstensi php_zip harus di enabled.
* Composer jika ingin melakukan backup selain ke local dan ftp.

## Panduan

#### Instalasi

Download repository ini, upload dan extract ke hosting kamu.

#### Menyiapkan File Konfigurasi

Backuper membutuhkan sebuah konfigurasi untuk menyimpan pengaturan backup kamu.

Buat file `mysite.php` pada folder configs, lalu isikan kode berikut:

```php
<?php

defined('BACKUPER_PATH') or die('No direct access script allowed');

return [
    // Path ke folder aplikasi
    'entry' => '/home/usernamehosting/public_html',

    // Nama file backup
    'output' => date('ymd_hi'),

    // Daftar koneksi database yang ingin di backup
    'databases' => [
        'db_a' => [
            'host'      => 'localhost',
            'username'  => 'username_db_a',
            'password'  => 'p4ssw0rd_db_a',
            'database'  => 'nama_db_a',
        ],
        'db_b' => [
            'host'      => 'localhost',
            'username'  => 'username_db_b',
            'password'  => 'p4ssw0rd_db_b',
            'database'  => 'nama_db_b',
        ],
    ],

    // Daftar file yang ingin di backup
    'files' => [
        'assets/*',
        'uploads/foto/*',
        'uploads/produk/*'
    ],

    // Daftar pengaturan penyimpanan tempat backup
    'backups' => [
        'local' => [
            'driver'    => 'local',
            'root'      => BACKUPER_PATH.'/backups/myapp'
        ],
        'ftp' => [
            'driver'    => 'ftp',
            'host'      => 'ftp.websitekamu.com',
            'port'      => 21,
            'username'  => 'akunftp@websitekamu.com',
            'password'  => 'p4ssw0rd_ftpnya'
        ],
        'dropbox' => [
            'driver'    => 'dropbox',
            'token'     => "[token kamu disini]",
        ]
    ]
];
```

> Jika kamu ingin backup ke dropbox, kamu terlebih dahulu harus menginstall 'srmklive/flysystem-dropbox-v2' melalui composer.

#### Menjalankan Backup

Buat sebuah cronjob baru, masukkan command berikut:

```
php /home/usernamehosting/folder/ke/backuper/backuper.php backup configs/mysite.php
```

## Menggunakan Driver Lain

Untuk saat ini backuper hanya mendaftarkan 3 buah driver resolver yaitu 'local', 'ftp' dan 'dropbox'.
Jika kamu ingin menggunakan adapter filesystem lain, kamu dapat mencoba beberapa cara berikut:

#### Mendaftarkan Instance Filesystem ke Dalam Konfigurasi

```php
return [
    'entry' => '/home/usernamehosting/public_html',
    'output' => date('ymd_hi'),
    'databases' => [
        ...
    ],
    'files' => [
        ...
    ],
    'backups' => [
        'google-drive' => function() {
            // buat adapter filesystem disini
            // kemudian return instance filesystem
            return new Filesystem($adapter);
        }
    ]
];
```

#### Membuat Driver Resolver Baru

Buat file baru di `src/DriverResolvers`, contoh `GoogleDriveDriverResolver.php`.

```php
<?php

namespace Emsifa\Backuper\DriverResolvers;

use League\Flysystem\Filesystem;

class GoogleDriveDriverResolver extends BaseDriverResolver
{

    public function makeFilesystem(array $params)
    {
        $this->requireParams(['client_id', 'client_secret', 'refresh_token'], $params);
        // buat adapter google drive disini
        // return instance filesystem
        return new Filesystem($adapter);
    }

}
```

Kemudian daftarkan driver resolver tersebut pada file `backuper.php`.

```php
...

$app = new App;

Backuper::registerDriver('google-drive', new Emsifa\DriverResolvers\GoogleDriveDriverResolver());

...
```

Selanjutnya untuk menggunakannya kamu dapat memasukkan array sebagai berikut pada konfigurasi backup:

```php
return [
    'entry' => '/home/usernamehosting/public_html',
    'output' => date('ymd_hi'),
    'databases' => [
        ...
    ],
    'files' => [
        ...
    ],
    'backups' => [
        'google-drive' => [
            'driver'            => 'google-drive',
            'client_id'         => '[client id]',
            'client_secret'     => '[client_secret]',
            'refresh_token'     => '[refresh token]'
        ]
    ]
];
```
