# Eu WorkFlow Plugin

## Overview
Eu WorkFlow, WordPress yönetici panelinde aday ve müşteri kayıtlarını tek ekranda toplayan bir iş akışı eklentisidir. Kayıtları oluşturmayı, güncellemeyi ve atamayı kolaylaştıran modern bir arayüz ile birlikte gelir.

## Öne Çıkan Özellikler
- **Kayıt Oluşturma Paneli**: Yeni kayıt formu tek tıkla gizlenip gösterilebilir ve Fluent Forms verileriyle uyumlu sabit alanlar içerir.
- **Gerçek Zamanlı Arama**: İsim, telefon veya e-posta ile filtreleme yapabilir; otomatik tamamlama önerileri sunar.
- **Kişi Kartları**: Her kartta iletişim bilgileri, statü rozetleri, temsilci notu ve doküman kategorileri yer alır.
- **Dosya Yönetimi**: Diploma, transkript, SGK dökümü, CV ve diğer belgeler için kategori bazlı yükleme alanları ve onay akışı bulunur.
- **İçerik Düzenleme**: Kayıt detaylarında satır içi düzenleme, not kaydetme, görüşme takibi ve statü güncelleme aksiyonları mevcuttur.
- **Toplu İşlemler**: Birden fazla kaydı seçip statü güncellemesi ve kullanıcı ataması yapılabilir.
- **Silme ve Loglama**: Kayıtlar güvenli şekilde silinebilir; işlemler etkinlik tablosuna kaydedilir.

## Roller ve Yetkiler
- **Eu WorkFlow Yöneticisi** (`wfs_manager`): Eu WorkFlow ekranındaki tüm kayıtlara erişebilir, atama yapabilir ve dosyaları yönetebilir.
- **Atama Yapabilen Kullanıcılar**: `wfs_assign_records` yetkisine sahip roller yalnızca kendilerine atanan kayıtları görür, gerektiğinde statü ve görüşme bilgilerini güncelleyebilir.

## Teknik Notlar
- AJAX istekleri nonce ve yetki kontrollerinden geçer.
- Dosya yüklemeleri WordPress yükleme dizinine kaydedilir; boyut ve MIME tipi doğrulamaları yapılır.
- Statü tanımları yönetici panelindeki ayarlar sayfasından özelleştirilebilir.

## Geliştirme ve Test
- Kod değişiklikleri PHP sözdizim denetiminden (`php -l`) geçirilmelidir.
- Ek testler için WordPress birim test yapısı veya manuel QA önerilir.

