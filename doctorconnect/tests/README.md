# Selenium tests — DoctorConnect

25 automated browser tests covering all four roles.

## Requirements

```
pip3 install selenium
```

Chrome must be installed. Selenium Manager downloads the matching
chromedriver on the first run, so nothing else needs to be set up.

XAMPP Apache and MySQL must be running, with the site reachable at
`http://localhost/Web-Technology-/doctorconnect`.

## Running

```
python3 tests/test_doctorconnect.py
```

Watch the browser do the work:

```
HEADLESS=0 python3 tests/test_doctorconnect.py
```

Point the suite at a different URL:

```
BASE_URL=http://localhost/doctorconnect python3 tests/test_doctorconnect.py
```

## What is covered

| Group | Tests | What is checked |
|-------|-------|-----------------|
| Authentication | 1–4 | Login page, wrong password, each role's landing page, private pages redirect |
| Role separation | 5–7 | A patient cannot open admin, doctor or front desk pages |
| Patient | 8–11 | Dashboard summary, doctor search, booking, cancelling |
| Doctor | 12–14 | Daily schedule, writing a visit note, cannot open another doctor's visit |
| Receptionist | 15–17 | Queue page, walk-in form, booking a walk-in |
| Admin | 18–21 | Overview, add/remove department, referential integrity refusals |
| Security | 22–25 | SQL injection attempt, session id regenerated, HttpOnly cookie, logout |

## Notes on the design of the suite

* **Tests create their own data.** Test 13 books an appointment as the
  patient before logging in as the doctor to complete it, so it never
  depends on rows left behind by an earlier run.
* **Dates are random.** Each doctor has eight slots per day. Booking
  tests pick a random day between 30 and 900 days ahead so repeated runs
  do not fill one date and start failing for the wrong reason.
* **Slots are re-read on every attempt.** `book.php` greys out slots that
  are already taken, so the list is fetched again after each reload
  rather than being captured once.
* **Text is compared without case.** Several labels use
  `text-transform: uppercase`, so the rendered text is `COMPLETED VISITS`
  even though the HTML says `Completed visits`.
* **The login button is clicked through JavaScript.** Chrome's offer to
  save the password floats over the page and swallows the next click.
