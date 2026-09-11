# Budget App

Laravel REST API aplikacija za praćenje i podelu zajedničkih troškova između korisnika.

Aplikacija omogućava kreiranje grupa, dodavanje članova, evidentiranje troškova, automatsku podelu iznosa između članova grupe, praćenje dugovanja i evidentiranje izmirenja dugova.

Projekat je razvijen kao seminarski rad i testira se putem Postman aplikacije.

---

## Funkcionalnosti

Aplikacija podržava sledeće funkcionalnosti:

- registracija korisnika
- prijava korisnika
- odjava korisnika
- autentifikacija pomoću Laravel Sanctum tokena
- uloge korisnika:
    - admin
    - user
    - guest kao neprijavljeni korisnik
- administracija korisnika i promena uloga
- kreiranje, prikaz, izmena i brisanje grupa
- dodavanje i uklanjanje članova grupe
- kreiranje, prikaz, izmena i brisanje kategorija troškova
- kreiranje, prikaz, izmena i brisanje troškova
- automatska podela troška između članova grupe
- praćenje dugovanja korisnika
- računanje stanja korisnika unutar grupe
- evidentiranje izmirenja dugovanja
- upload računa uz trošak
- pretraga troškova
- filtriranje troškova
- sortiranje troškova
- paginacija rezultata
- izvoz troškova u CSV format
- konverzija valuta korišćenjem eksternog REST API servisa
- prikaz državnih praznika korišćenjem eksternog REST API servisa
- forgot password funkcionalnost
- resetovanje lozinke
- slanje email poruka preko Gmail SMTP servisa
- kontrola pristupa resursima na osnovu članstva u grupi
- administratori imaju pristup svim podacima
- korišćenje transakcija baze podataka pri kreiranju troškova

---

## Tehnologije

Projekat koristi:

- PHP
- Laravel
- MySQL
- Laravel Sanctum
- Eloquent ORM
- REST API
- Postman
- Gmail SMTP
- Git
- GitHub

Eksterni servisi:

- Frankfurter API za kursnu konverziju valuta
- Nager.Date API za državne praznike

---

## Pokretanje projekta na lokalnoj mašini

Za lokalno pokretanje projekta potrebno je imati instalirane sledeće alate:

- PHP
- Composer
- MySQL
- Git
- XAMPP ili drugi lokalni MySQL server
- Postman za testiranje API ruta

### 1. Kloniranje repozitorijuma

Projekat se preuzima sa GitHub repozitorijuma pomoću komande:

```bash
git clone https://github.com/elab-development/serverske-veb-tehnologije-2025-26-vebaplikacijazabudzetiranje_2022_0211.git
```

Nakon kloniranja potrebno je pozicionirati se u direktorijum projekta:

```bash
cd budget-app
```

### 2. Instalacija zavisnosti

PHP/Laravel zavisnosti instaliraju se pomoću Composer-a:

```bash
composer install
```

### 3. Kreiranje `.env` fajla

Potrebno je napraviti lokalni `.env` fajl na osnovu `.env.example` fajla.

Na Windows sistemu:

```bash
copy .env.example .env
```

Na Linux/macOS sistemu:

```bash
cp .env.example .env
```

Zatim se generiše Laravel application key:

```bash
php artisan key:generate
```

### 4. Podešavanje baze podataka

Potrebno je pokrenuti MySQL server i kreirati praznu bazu podataka, na primer:

```text
budget_app
```

Zatim u `.env` fajlu podesiti parametre za povezivanje sa bazom:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=budget_app
DB_USERNAME=root
DB_PASSWORD=
```

Vrednosti `DB_DATABASE`, `DB_USERNAME` i `DB_PASSWORD` potrebno je prilagoditi lokalnoj MySQL konfiguraciji.

### 5. Kreiranje tabela i test podataka

Za izvršavanje migracija i popunjavanje baze test podacima pokrenuti:

```bash
php artisan migrate --seed
```

Ukoliko je potrebno potpuno obrisati postojeće tabele, ponovo ih kreirati i popuniti test podacima, može se koristiti:

```bash
php artisan migrate:fresh --seed
```

### 6. Kreiranje storage linka

Pošto aplikacija podržava upload računa uz troškove, potrebno je kreirati simbolički link za `storage`:

```bash
php artisan storage:link
```

### 7. Podešavanje email servisa

Za funkcionalnosti zaboravljene i promene lozinke potrebno je podesiti SMTP parametre u `.env` fajlu:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

Za Gmail se kao lozinka koristi Google App Password.

Pravi email, App Password i druge poverljive podatke ne treba postavljati na GitHub.

Nakon promene konfiguracije može se izvršiti:

```bash
php artisan config:clear
```

### 8. Pokretanje Laravel servera

Aplikacija se pokreće komandom:

```bash
php artisan serve
```

Nakon uspešnog pokretanja aplikacija je podrazumevano dostupna na:

```text
http://127.0.0.1:8000
```

API rute dostupne su preko `/api` prefiksa, na primer:

```text
http://127.0.0.1:8000/api/login
http://127.0.0.1:8000/api/groups
http://127.0.0.1:8000/api/expenses
```

Za testiranje zaštićenih API ruta potrebno je prvo izvršiti registraciju ili prijavu korisnika i dobijeni Laravel Sanctum token poslati kroz Postman kao Bearer Token.

## Autor

Projekat je izrađen u okviru fakultetskog projekta.

- Lazar Vasiljevic
