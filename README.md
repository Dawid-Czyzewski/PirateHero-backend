# Freak Legends API

REST API gry przeglądarkowej **FREAK LEGENDS**. Frontend (Next.js) jest w osobnym repo i łączy się z API z `http://localhost:3000`.

Stack: Symfony 7.4 LTS, API Platform 4, Doctrine ORM + Migrations, PostgreSQL 18, JWT (LexikJWTAuthenticationBundle) + refresh tokeny (GesdinetJWTRefreshTokenBundle), NelmioCorsBundle, Symfony Rate Limiter.

Architektura: **DDD + porty i adaptery** (opis niżej, w sekcji [Architektura](#architektura)).

## Wymagania

- **PHP 8.5.12+** (NTS x64) z rozszerzeniami `pdo_pgsql`, `pgsql`, `intl`, `sodium`, `openssl`, `mbstring`, `curl`, `fileinfo`, `zip` i OPcache (w PHP 8.5 jest wbudowany). `composer.json` wymaga `php >=8.5` i ustawia `config.platform.php` na `8.5.12`.
- Composer 2
- PostgreSQL 18 (usługa Windows na `127.0.0.1:5432`)
- [Symfony CLI](https://symfony.com/download) 5.x. Plik `.php-version` (`8.5`) wybiera właściwe PHP, gdy w systemie jest kilka wersji.

### Instalacja PHP 8.5 na Windows (obok XAMPP)

1. Pobierz `php-8.5.12-1-nts-Win32-vs17-x64.zip` (VS17 x64 Non Thread Safe) ze strony <https://www.php.net/downloads.php?os=windows> i porównaj SHA256 z sumą podaną na tej stronie (`Get-FileHash .\php-8.5.12-1-nts-Win32-vs17-x64.zip -Algorithm SHA256`).
2. Rozpakuj do `C:\php\8.5`, skopiuj `php.ini-development` jako `php.ini` i ustaw w nim:

   ```ini
   memory_limit = 512M
   extension_dir = "ext"
   extension=curl
   extension=fileinfo
   extension=intl
   extension=mbstring
   extension=openssl
   extension=pdo_pgsql
   extension=pgsql
   extension=sodium
   extension=zip
   date.timezone = Europe/Warsaw
   opcache.enable=1
   curl.cainfo = "C:/php/8.5/extras/ssl/cacert.pem"
   openssl.cafile = "C:/php/8.5/extras/ssl/cacert.pem"
   ```

   `cacert.pem` pobierz z <https://curl.se/ca/cacert.pem> (suma w `cacert.pem.sha256`) do `C:\php\8.5\extras\ssl\`.

3. Dodaj `C:\php\8.5` do PATH użytkownika **przed** `D:\xamp\php` (PowerShell, potem otwórz nowy terminal):

   ```powershell
   $p = [Environment]::GetEnvironmentVariable('Path','User') -split ';' | Where-Object { $_ -and $_ -ne 'C:\php\8.5' }
   $i = [Array]::IndexOf($p, 'D:\xamp\php')
   [Environment]::SetEnvironmentVariable('Path', ((@($p[0..($i-1)]) + 'C:\php\8.5' + @($p[$i..($p.Count-1)])) -join ';'), 'User')
   ```

   Albo: Ustawienia → System → Informacje → Zaawansowane ustawienia systemu → Zmienne środowiskowe → `Path` użytkownika → Nowy `C:\php\8.5` → Przenieś w górę nad `D:\xamp\php`.

### Sprawdzenie środowiska (FL0)

```bash
php -v                     # PHP 8.5.12 (cli) ... (NTS Visual C++ 2022 x64)
where php                  # pierwsza linia: C:\php\8.5\php.exe
php -m                     # m.in. curl, fileinfo, intl, mbstring, openssl, pdo_pgsql, pgsql, sodium, zip, Zend OPcache
composer -V                # Composer 2.x, "PHP version 8.5.12 (C:\php\8.5\php.exe)"
symfony -V                 # Symfony CLI 5.x
symfony local:php:list     # 8.5.12 z C:\php\8.5 oznaczone jako System
psql --version             # psql (PostgreSQL) 18.x
php bin/console doctrine:migrations:status
php bin/console lint:container
```

Usługa PostgreSQL (PowerShell): `Get-Service *postgres*` (Running) i `Test-NetConnection 127.0.0.1 -Port 5432` (`TcpTestSucceeded : True`).

## Instalacja krok po kroku

```bash
cd D:\portfolio\FameGame\freak-legends-api
composer install
```

1. Utwórz `.env.local` (jest w `.gitignore`, nigdy go nie commituj):

   ```dotenv
   DATABASE_URL="postgresql://postgres:TWOJE_HASLO@127.0.0.1:5432/freak_legends_api?serverVersion=18&charset=utf8"
   JWT_PASSPHRASE=dlugie_losowe_haslo
   ```

   Dla testów utwórz `.env.test.local` z tymi samymi dwiema zmiennymi (środowisko `test` nie czyta `.env.local`). Baza testowa dostaje automatycznie sufiks `_test`.

2. Wygeneruj klucze JWT (trafiają do `config/jwt/`, folder jest w `.gitignore`):

   ```bash
   php bin/console lexik:jwt:generate-keypair
   ```

   > Windows: jeśli pojawi się `error:80000003:system library::No such process`, wskaż plik `openssl.cnf` z paczki PHP:
   > `$env:OPENSSL_CONF="C:\php\8.5\extras\ssl\openssl.cnf"` (PowerShell) i uruchom polecenie ponownie.

3. Baza i migracje:

   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

4. (opcjonalnie) Dane przykładowe – użytkownik `demo@freaklegends.local` / `password123` i 5 kolejnych z tym samym hasłem. Uwaga: polecenie czyści tabele.

   ```bash
   php bin/console doctrine:fixtures:load
   ```

5. Serwer:

   ```bash
   symfony serve
   ```

   Serwer ruszy na <http://127.0.0.1:8000> z PHP 8.5 (CLI wybiera wersję z `.php-version`). Bez Symfony CLI: `php -S 127.0.0.1:8000 -t public`.

Dokumentacja API (Swagger UI): <http://127.0.0.1:8000/api/docs>

## Endpointy

| Metoda | Ścieżka | Dostęp | Opis |
|---|---|---|---|
| `POST` | `/api/auth/register` | publiczny, limit 5 / 15 min na IP | Rejestracja: `email`, `nick` (3–16 znaków), `password` (min. 8). Zwraca 201 i użytkownika, 422 przy błędach walidacji lub zajętym email/nicku, 429 po przekroczeniu limitu. |
| `POST` | `/api/auth/login` | publiczny, limit 5 / min na email + IP | Logowanie: `email`, `password`. Zwraca `token` (JWT, ważny 1 dzień) i `refresh_token` (30 dni). |
| `POST` | `/api/auth/refresh` | publiczny | `refresh_token` → nowy `token` i nowy `refresh_token` (stary przestaje działać). |
| `GET` | `/api/me` | `ROLE_USER` (Bearer JWT) | Zalogowany użytkownik. 401 bez tokena. |
| `POST` | `/api/characters` | `ROLE_USER` (Bearer JWT) | Tworzy postać zalogowanego gracza. Body: `face`, `hair`, `beard`, `tattoo` (0–7), `skin` (0–4). Zwraca 201 i postać, 401 bez tokena, 409 gdy gracz ma już postać (`Character already exists.`), 422 przy brakującym polu lub wartości spoza zakresu. |
| `GET` | `/api/me/character` | `ROLE_USER` (Bearer JWT) | Postać zalogowanego gracza: 200, 401 bez tokena, 404 gdy gracz nie ma postaci (`Character not found.`). |
| `GET` | `/api/health` | publiczny | Stan API i połączenia z bazą: `{"status":"ok","db":"ok"}` (200) albo `{"status":"error","db":"error"}` (503). |
| `GET` | `/api/docs` | publiczny | Dokumentacja OpenAPI / Swagger UI. |

Wszystkie pozostałe ścieżki pod `/api` wymagają `ROLE_USER`. Hasło nigdy nie trafia do odpowiedzi, bo API zwraca osobny `UserResource` zbudowany z `UserView`, a nie encję. CORS przepuszcza tylko `http://localhost:3000` (`CORS_ALLOW_ORIGIN` w `.env`).

## Przykłady curl

```bash
# Health
curl http://127.0.0.1:8000/api/health
# {"status":"ok","db":"ok"}

# Rejestracja
curl -X POST http://127.0.0.1:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"gracz@example.com","nick":"Cebulak","password":"tajnehaslo"}'

# Logowanie -> {"token":"...","refresh_token":"..."}
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"gracz@example.com","password":"tajnehaslo"}'

# Zalogowany użytkownik
curl http://127.0.0.1:8000/api/me -H "Authorization: Bearer TOKEN"

# Odświeżenie tokena
curl -X POST http://127.0.0.1:8000/api/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{"refresh_token":"REFRESH_TOKEN"}'

# Utworzenie postaci (201)
curl -X POST http://127.0.0.1:8000/api/characters \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN" \
  -d '{"face":3,"hair":1,"beard":7,"tattoo":0,"skin":4}'

# Moja postać (200 albo 404)
curl http://127.0.0.1:8000/api/me/character -H "Authorization: Bearer TOKEN"
```

Odpowiedź z postacią (201 i 200):

```json
{
  "id": "01a1a83d-edcd-72a6-b36f-c1ed2127b9ee",
  "level": 1,
  "maxHp": 15,
  "stats": {"strength": 1, "endurance": 1, "technique": 1, "charisma": 1},
  "wallet": {"cebuliony": 100, "zloteZeby": 10, "kondycha": 100, "zasiegi": 0, "medale": 0},
  "appearance": {"face": 3, "hair": 1, "beard": 7, "skin": 4, "tattoo": 0}
}
```

## Testy i jakość kodu

```bash
php bin/phpunit                        # wszystkie testy
php bin/phpunit --testsuite unit       # tylko jednostkowe (bez bazy)
php bin/phpunit --testsuite integration # repozytoria Doctrine na bazie testowej
php bin/phpunit --testsuite functional # tylko funkcjonalne (baza freak_legends_api_test)
vendor/bin/phpstan analyse             # PHPStan, poziom 8
vendor/bin/deptrac analyse             # granice warstw DDD (pakiet deptrac/deptrac, następca qossmic/deptrac)
vendor/bin/php-cs-fixer fix --dry-run --diff
```

Testy funkcjonalne korzystają z `ApiTestCase` i klienta HTTP z API Platform (`tests/Functional/FunctionalTestCase.php`). Testy integracyjne i funkcjonalne działają na osobnej bazie `freak_legends_api_test`. Foundry (`ResetDatabase`, tryb `migrate`) raz na przebieg buduje ją od zera z migracji, a `dama/doctrine-test-bundle` zamyka każdy test w transakcji wycofywanej po teście, więc testy są od siebie niezależne i sprawdzają też same migracje. Dane logowania do niej są w `.env.test.local`. PHPStan korzysta z kontenera z `var/cache/dev`, więc po zmianach w konfiguracji uruchom najpierw `php bin/console cache:warmup`.

Testy jednostkowe nie mockują domeny: handlery i procesory dostają `InMemoryUserRepository` i `FakePasswordHasher` z `tests/Double/`.

## Architektura

Kod jest podzielony na **bounded contexty**: `Identity` (konta i logowanie), `Character` (postać gracza: tworzenie i odczyt) i `Shared` (shared kernel: `Uuid`, `UserId`, port `CurrentUser`, healthcheck). Kolejne konteksty gry, np. `Fight`, dochodzą jako nowe katalogi w `src/` o tej samej budowie.

Każdy kontekst ma 4 warstwy. Zależności idą tylko do środka:

| Warstwa | Co zawiera | Może zależeć od |
|---|---|---|
| `Domain` | Identity: agregat `User`, VO `Email`, `Nick`, `PlainPassword`, `HashedPassword`, porty `UserRepository` i `PasswordHasher`. Character: agregat `Character`, VO `CharacterId`, `Level`, `Stats`, `Wallet`, `Appearance`, `HitPoints`, port `CharacterRepository`. Shared: `Uuid`, `UserId` | tylko `Shared/Domain` (czysty PHP) |
| `Application` | Przypadki użycia: komendy (`RegisterUser`, `CreateCharacter`) i zapytania (`GetUser`, `GetMyCharacter`) z handlerami, read modele `UserView` i `CharacterView`, port `CurrentUser` (Shared) | `Domain`, PSR (`ClockInterface`) |
| `Infrastructure` | **Adaptery**: repozytoria Doctrine (`DoctrineUserRepository`, `DoctrineCharacterRepository`), typy Doctrine dla VO (`user_id`, `character_id`, `email`, `nick`, `hashed_password`), mapowanie XML z embeddables, `SecurityUser` + provider, hasher Symfony, refresh tokeny, fixtures | `Domain`, `Application`, framework |
| `Presentation` | Wejście HTTP: zasoby API Platform (`UserResource`, `RegisterUserInput`, `CharacterResource`, `CreateCharacterInput`), procesory/providery, kontrolery | `Application`, `Domain`, `Infrastructure`, framework |

Zasady:

- **Domena nie zna frameworka.** Doctrine mapuje ją z XML (`Infrastructure/Persistence/Doctrine/Mapping`), a Symfony Security widzi adapter `SecurityUser`, nie encję.
- **Reguły biznesowe są w Value Objects** (np. ksywa 3–16 znaków, hasło min. 8, email małymi literami). Walidator na `RegisterUserInput` powtarza je tylko po to, żeby API zwróciło od razu wszystkie błędy pól (422).
- **Unikalność** sprawdza `RegisterUserHandler`, a wyścig dwóch rejestracji naraz łapie unikalny indeks w bazie (`UserAlreadyExists`).
- **Porty → adaptery** są połączone w `config/services.yaml`.
- **Granice pilnuje Deptrac** (`deptrac.yaml`). Domena każdego kontekstu to osobna warstwa (`IdentityDomain`, `CharacterDomain`) i może zależeć wyłącznie od `SharedDomain`. Np. import klasy Symfony albo encji `User` w `Character/Domain` kończy się błędem.
- **Tabela `characters`**: `Stats`, `Wallet`, `Appearance` i `Level` są embeddables (kolumny bez prefiksu), `user_id` jest unikalny (jedna postać na konto, `CharacterAlreadyExists`) i ma klucz obcy `fk_characters_user_id` do `users(id)` z `ON DELETE CASCADE`. Klucz obcy dodaje listener schematu `CharacterOwnerForeignKey`, bo agregaty odwołują się do siebie tylko przez ID, bez asocjacji Doctrine.
- **`UserId` jest w shared kernel** (`Shared/Domain/Model`), więc `Character` odwołuje się do właściciela tylko przez to VO, bez zależności od encji `User`.
- **Zalogowany gracz** jest dostępny przez port `Shared\Application\Security\CurrentUser` (adapter `SymfonyCurrentUser` w Identity), więc `Character` nie zna `SecurityUser` ani JWT.
- **Postać na start** (`Character::create`): poziom 1, wszystkie cechy (`strength`, `endurance`, `technique`, `charisma`) = 1, portfel: 100 Cebulionów, 10 Złotych Zębów, 100 Kondychy, 0 Zasięgów, 0 Medali. `maxHp() = endurance × 10 + level × 5` (na starcie 15). Wygląd: `face`, `hair`, `beard`, `tattoo` 0–7, `skin` 0–4.

Przepływ rejestracji:

```
POST /api/auth/register
  → RegisterUserInput (walidacja pól)
  → RegisterUserProcessor (limit IP, mapowanie błędów na 422)
  → RegisterUserHandler (VO, unikalność, hash, User::register)
  → UserRepository ← DoctrineUserRepository
  → GetUserHandler → UserView → UserResource (JSON)
```

## Struktura

```
src/
  Identity/
    Domain/
      Model/          User (agregat), Email, Nick, PlainPassword, HashedPassword
      Repository/     UserRepository (port)
      Service/        PasswordHasher (port)
      Exception/      InvalidUserData, UserAlreadyExists, UserNotFound
    Application/
      Command/RegisterUser/   RegisterUserCommand + RegisterUserHandler
      Query/GetUser/          GetUserQuery + GetUserHandler + UserView
    Infrastructure/
      Persistence/Doctrine/   DoctrineUserRepository, Type/ (Email, Nick, HashedPassword), Mapping/User.orm.xml, Fixtures/
      Security/               SecurityUser, SecurityUserProvider, SymfonyPasswordHasher, RefreshToken
    Presentation/
      Api/                    UserResource, RegisterUserInput, State/ (RegisterUserProcessor, MeProvider)
  Character/
    Domain/
      Model/          Character (agregat), CharacterId, Level, Stats, Wallet, Appearance, HitPoints
      Repository/     CharacterRepository (port)
      Exception/      InvalidCharacterData, CharacterAlreadyExists, CharacterNotFound
    Application/
      Command/CreateCharacter/  CreateCharacterCommand + CreateCharacterHandler
      Query/GetMyCharacter/     GetMyCharacterQuery + GetMyCharacterHandler + CharacterView
    Infrastructure/
      Persistence/Doctrine/   DoctrineCharacterRepository, Type/CharacterIdType, Mapping/ (Character + embeddables), Schema/CharacterOwnerForeignKey
    Presentation/
      Api/                    CharacterResource, CreateCharacterInput, Output/, State/ (CreateCharacterProcessor, MyCharacterProvider)
  Shared/
    Application/Security/     CurrentUser (port)
    Domain/
      Model/          Uuid, UserId
      Exception/      InvalidIdentifier
    Infrastructure/
      Persistence/Doctrine/Type/  UuidType (baza), UserIdType
    Presentation/Http/        HealthController
tests/
  Unit/         testy jednostkowe per kontekst i warstwa (VO Identity, Character i Shared, maxHp, wartości startowe postaci, handlery z repozytorium in-memory)
  Integration/  testy repozytoriów na bazie testowej (zapis i odczyt postaci, jedna postać na konto, klucz obcy, kaskada)
  Functional/   testy HTTP na ApiTestCase: register, login, refresh, me z tokenem i bez, postać (401, 201, 409, 422, 200, 404), health, docs, CORS
  Double/       InMemoryUserRepository, InMemoryCharacterRepository, FakePasswordHasher, FixedCurrentUser, UserMother
  Factory/      fabryki Foundry
migrations/     migracje Doctrine
deptrac.yaml    reguły warstw
```
