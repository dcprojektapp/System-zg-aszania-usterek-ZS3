# Szkolny System Zgłaszania Usterek

Nowoczesna aplikacja internetowa do zarządzania zgłoszeniami awarii w szkole. System umożliwia nauczycielom szybkie zgłaszanie usterek, a administratorom ich monitorowanie i zmianę statusu.

## ✨ Funkcjonalności

- **Nowoczesny Interfejs (Glassmorphism)**: Responsywny i estetyczny design.
- **System Zgłoszeń**: Formularz z walidacją, wybór lokalizacji i typu usterki.
- **Panel Administratora**:
  - Przegląd wszystkich zgłoszeń.
  - Filtrowanie po statusie (Nowe, W trakcie, Naprawione, Rozwiązane).
  - Zmiana statusu zgłoszenia.
  - **Usuwanie zgłoszeń**.
- **Logowanie**: Bezpieczny system logowania za pomocą adresu email i hasła.

## 🚀 Instalacja i Uruchomienie

### Wymagania
- Serwer z obsługą PHP 8.0++ (np. XAMPP, Laragon).
- Baza danych MySQL/MariaDB.

### Krok po kroku

1.  **Kopiowanie plików**:
    Skopiuj pliki projektu do folderu serwera (np. `C:\xampp\htdocs\szkolny_system_zglaszania_usterek`).

2.  **Konfiguracja Bazy Danych**:
    - Otwórz plik `config/db.php` i sprawdź dane połączenia (domyślnie: localhost, root, bez hasła).
    - Uruchom XAMPP (Apache i MySQL).

3.  **Instalacja Automatyczna**:
    - Otwórz w przeglądarce: `http://localhost/szkolny_system_zglaszania_usterek/public/setup.php`
    - Skrypt automatycznie utworzy bazę danych, tabele oraz użytkowników.

## 🔐 Dane Logowania

System posiada domyślnie utworzone konta:

### Konto Nauczyciela (Zgłaszający)
- **Email**: `nauczyciel@zs3.lukow.pl`
- **Hasło**: `nauczycielZS3`

### Konto Administratora
- **Email**: `admin@zs3.lukow.pl`
- **Hasło**: `AdminZS3`

## 🛠 Technologie
- **Frontend**: HTML5, CSS3 (Custom Glassmorphism), Bootstrap 5, JavaScript.
- **Backend**: PHP 8.
- **Baza Danych**: MySQL.
