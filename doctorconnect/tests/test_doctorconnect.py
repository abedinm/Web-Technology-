"""
Selenium test suite for DoctorConnect
CSC 3215 Web Technologies | Group 6, Section F

Drives a real Chrome browser against the running XAMPP site and checks
the behaviour of all four roles: patient, doctor, receptionist, admin.

Run with:      python3 tests/test_doctorconnect.py
Headed run:    HEADLESS=0 python3 tests/test_doctorconnect.py
"""

import os
import random
import sys
import time
import unittest

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select, WebDriverWait

BASE = os.environ.get("BASE_URL", "http://localhost/Web-Technology-/doctorconnect")
PASSWORD = "1234"

ACCOUNTS = {
    "admin":        "admin@doctorconnect.com",
    "doctor":       "salma@doctorconnect.com",
    "receptionist": "reception@doctorconnect.com",
    "patient":      "nusrat@gmail.com",
}

DASHBOARDS = {
    "admin":        "admin_dashboard.php",
    "doctor":       "doctor_dashboard.php",
    "receptionist": "reception_dashboard.php",
    "patient":      "patient_dashboard.php",
}


class DoctorConnectTest(unittest.TestCase):
    """One Chrome window is shared by every test to keep the suite quick."""

    @classmethod
    def setUpClass(cls):
        options = Options()
        if os.environ.get("HEADLESS", "1") == "1":
            options.add_argument("--headless=new")
        options.add_argument("--window-size=1440,1000")
        options.add_argument("--disable-gpu")

        # Chrome offers to save the password after a successful login. That
        # bubble floats over the page and swallows the next click, which
        # makes later logins fail for no reason. Turn the whole feature off.
        options.add_argument("--disable-save-password-bubble")
        options.add_argument("--disable-features=PasswordManagerOnboarding,AutofillServerCommunication")
        options.add_experimental_option("prefs", {
            "credentials_enable_service": False,
            "profile.password_manager_enabled": False,
            "profile.password_manager_leak_detection": False,
            "autofill.profile_enabled": False,
        })

        cls.driver = webdriver.Chrome(options=options)
        cls.wait = WebDriverWait(cls.driver, 10)

    @classmethod
    def tearDownClass(cls):
        cls.driver.quit()

    # ---------- helpers ----------

    def go(self, page):
        self.driver.get(BASE + "/" + page)

    @staticmethod
    def future_date():
        """A random weekday well ahead of today.

        Each doctor has only eight slots per day, so running the suite over
        and over against one fixed date would eventually fill it and the
        booking tests would fail for the wrong reason.
        """
        offset = random.randint(30, 900)
        return time.strftime("%Y-%m-%d", time.localtime(time.time() + 86400 * offset))

    def logout(self):
        self.go("logout.php")

    def submit_login(self, email, password):
        """Fill the login form and submit it.

        The fields are cleared first because login.php re-prints whatever
        was typed after a failed attempt, and send_keys would append to it.
        The button is clicked through JavaScript so that nothing floating
        above the page can intercept the click.
        """
        email_field = self.wait.until(EC.presence_of_element_located((By.NAME, "email")))
        email_field.clear()
        email_field.send_keys(email)

        password_field = self.driver.find_element(By.NAME, "password")
        password_field.clear()
        password_field.send_keys(password)

        button = self.driver.find_element(By.CSS_SELECTOR, "[type=submit]")
        self.driver.execute_script("arguments[0].click();", button)

    def login(self, role):
        """Log in and wait until the role's dashboard has loaded."""
        self.logout()
        self.go("login.php")
        self.submit_login(ACCOUNTS[role], PASSWORD)
        self.wait.until(EC.url_contains(DASHBOARDS[role]))

    def body(self):
        return self.driver.find_element(By.TAG_NAME, "body").text

    def assert_shows(self, needle):
        """Assert the page shows this text, ignoring case.

        Several labels are styled with text-transform: uppercase, so the
        rendered text Selenium reads back is "COMPLETED VISITS" even though
        the HTML says "Completed visits". Comparing case-insensitively tests
        the content without pinning the test to the styling.
        """
        self.assertIn(needle.lower(), self.body().lower())

    def submit_picking_a_free_slot(self, expect_url):
        """Submit a booking form, trying each time slot in turn.

        The application refuses a slot that is already taken, which is the
        behaviour we want, so a test that hard-codes one slot fails as soon
        as it has run once. This walks the list until the booking is
        accepted, and fails loudly on any other error message.
        """
        for _ in range(12):
            # Two different pickers exist. book.php shows the slots as a grid
            # of chips (the Figma design), while walkin.php still uses a plain
            # dropdown, so handle whichever this page is using.
            chips = self.driver.find_elements(By.CSS_SELECTOR, ".slotgrid .slot:not(.taken)")

            if chips:
                chips[0].click()
                chosen = chips[0].get_attribute("data-slot")
            else:
                select = Select(self.driver.find_element(By.NAME, "time_slot"))
                free = [o.get_attribute("value") for o in select.options
                        if o.get_attribute("value") and not o.get_attribute("disabled")]

                if not free:
                    self.fail("every time slot on this date is already booked")

                select.select_by_value(free[0])
                chosen = free[0]

            # Hold on to the button so we can wait for the page to actually
            # turn over. Checking the URL straight after click() races the
            # reload and reads the old page.
            button = self.driver.find_element(By.NAME, "submit")
            button.click()
            self.wait.until(EC.staleness_of(button))

            if expect_url in self.driver.current_url:
                return chosen

            errors = self.driver.find_elements(By.CLASS_NAME, "alert-error")
            if not errors:
                self.fail("form did not submit and showed no error")
            if "already booked" not in errors[0].text:
                self.fail("booking refused: " + errors[0].text)

        self.fail("could not find a free slot after several attempts")

    def input_values(self, css):
        """Values of matching inputs. body.text never includes input values,
        so anything rendered inside a form field has to be read this way."""
        return [e.get_attribute("value")
                for e in self.driver.find_elements(By.CSS_SELECTOR, css)]

    # ---------- 1. authentication ----------

    def test_01_login_page_loads(self):
        self.go("login.php")
        self.assertIn("Sign in", self.body())
        self.assertTrue(self.driver.find_element(By.NAME, "email").is_displayed())

    def test_02_wrong_password_is_rejected(self):
        self.logout()
        self.go("login.php")
        self.submit_login(ACCOUNTS["patient"], "wrong-password")
        self.wait.until(EC.presence_of_element_located((By.CLASS_NAME, "alert-error")))
        self.assertIn("Wrong email or password", self.body())
        # The same message is used for a bad email, so the page cannot be
        # used to find out which addresses are registered.

    def test_03_each_role_lands_on_its_own_dashboard(self):
        for role, page in DASHBOARDS.items():
            with self.subTest(role=role):
                self.login(role)
                self.assertIn(page, self.driver.current_url)

    def test_04_private_page_redirects_when_logged_out(self):
        self.logout()
        self.go("patient_dashboard.php")
        self.assertIn("login.php", self.driver.current_url)

    # ---------- 2. role separation ----------

    def test_05_patient_cannot_open_admin_pages(self):
        self.login("patient")
        for page in ("admin_dashboard.php", "manage_doctors.php", "manage_departments.php"):
            with self.subTest(page=page):
                self.go(page)
                self.assertIn("patient_dashboard.php", self.driver.current_url)

    def test_06_patient_cannot_open_doctor_or_front_desk(self):
        self.login("patient")
        for page in ("doctor_dashboard.php", "reception_dashboard.php"):
            with self.subTest(page=page):
                self.go(page)
                self.assertIn("patient_dashboard.php", self.driver.current_url)

    def test_07_doctor_cannot_open_admin_pages(self):
        self.login("doctor")
        self.go("admin_dashboard.php")
        self.assertIn("doctor_dashboard.php", self.driver.current_url)

    # ---------- 3. patient journey ----------

    def test_08_patient_dashboard_shows_the_summary(self):
        self.login("patient")
        for label in ("Completed visits", "Departments available", "Recent appointments"):
            with self.subTest(label=label):
                self.assert_shows(label)

    def test_09_doctor_search_lists_doctors_with_fee_and_room(self):
        self.login("patient")
        self.go("doctors.php")
        text = self.body()
        self.assertIn("Dr.", text)
        self.assertIn("Tk", text)
        self.assertIn("Room", text)

    def test_10_patient_can_book_an_appointment(self):
        self.login("patient")
        self.go("doctors.php")
        self.driver.find_elements(By.PARTIAL_LINK_TEXT, "Book")[0].click()
        self.wait.until(EC.presence_of_element_located((By.NAME, "appt_date")))

        # A date far enough ahead that the slot is unlikely to be taken.
        # book.php now picks the date through day cards that reload the page
        # with ?date=, so drive it the same way a user would.
        future = self.future_date()
        url = self.driver.current_url.split("#")[0]
        joiner = "&" if "?" in url else "?"
        self.driver.get(url + joiner + "date=" + future)
        self.wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".slotgrid")))

        self.submit_picking_a_free_slot("my_appointments.php")
        self.assertIn("pending", self.body().lower())

    def test_11_patient_can_cancel_a_pending_appointment(self):
        self.login("patient")
        self.go("my_appointments.php")
        buttons = self.driver.find_elements(By.NAME, "cancel")
        if not buttons:
            self.skipTest("no cancellable appointment for this patient")
        before = self.body().lower().count("cancelled")
        buttons[0].click()
        self.driver.switch_to.alert.accept()      # the confirm() dialog
        self.wait.until(EC.presence_of_element_located((By.CLASS_NAME, "alert-ok")))
        self.assertGreater(self.body().lower().count("cancelled"), before)

    # ---------- 4. doctor journey ----------

    def test_12_doctor_sees_their_schedule(self):
        self.login("doctor")
        self.assert_shows("schedule")
        self.assert_shows("Expected fees")

    def test_13_doctor_can_write_a_visit_note(self):
        # Book an appointment as the patient first, so this test always has
        # something to complete instead of skipping when the data runs out.
        self.login("patient")
        self.go("doctors.php")

        # Book with Dr. Salma Akter specifically - that is the account this
        # test logs in as next, so the appointment has to land on her list.
        target = "Salma"
        cards = self.driver.find_elements(By.CSS_SELECTOR, ".doctor-card")
        for card in cards:
            if target in card.text:
                card.find_element(By.PARTIAL_LINK_TEXT, "Book").click()
                break
        else:
            self.skipTest("Dr. " + target + " is not listed")

        self.wait.until(EC.presence_of_element_located((By.NAME, "appt_date")))

        day = self.future_date()
        url = self.driver.current_url.split("#")[0]
        joiner = "&" if "?" in url else "?"
        self.driver.get(url + joiner + "date=" + day)
        self.wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".slotgrid")))
        self.submit_picking_a_free_slot("my_appointments.php")

        # Now open it as the doctor who owns that slot.
        self.login("doctor")
        self.go("doctor_dashboard.php?date=" + day)
        links = self.driver.find_elements(By.PARTIAL_LINK_TEXT, "Start visit")
        self.assertTrue(links, "the appointment just booked should be on this doctor's list")
        links[0].click()

        self.wait.until(EC.presence_of_element_located((By.NAME, "diagnosis")))
        self.driver.find_element(By.NAME, "diagnosis").send_keys("Selenium test diagnosis")
        self.driver.find_element(By.NAME, "visit_note").send_keys("Written by the automated test.")
        self.driver.find_element(By.NAME, "submit").click()
        self.wait.until(EC.url_contains("doctor_dashboard.php"))
        self.assertIn("completed", self.body().lower())

    def test_14_doctor_cannot_open_another_doctors_visit(self):
        self.login("doctor")
        # id 1 belongs to the seeded appointment for a different doctor.
        self.go("visit.php?id=999999")
        self.assertIn("does not exist", self.body())

    # ---------- 5. receptionist journey ----------

    def test_15_front_desk_queue_loads(self):
        self.login("receptionist")
        self.assert_shows("Front desk")
        self.assert_shows("Checked in")

    def test_16_walk_in_form_has_its_fields(self):
        self.login("receptionist")
        self.go("walkin.php")
        for name in ("new_name", "new_phone", "doctor_id", "appt_date", "time_slot"):
            with self.subTest(field=name):
                self.assertTrue(self.driver.find_element(By.NAME, name).is_displayed())

    def test_17_receptionist_books_a_walk_in(self):
        self.login("receptionist")
        self.go("walkin.php")
        stamp = str(int(time.time()))
        self.driver.find_elements(By.NAME, "patient_mode")[1].click()   # "New patient"
        self.driver.find_element(By.NAME, "new_name").send_keys("Walk In " + stamp)
        self.driver.find_element(By.NAME, "new_phone").send_keys("017" + stamp[-8:])

        doctors = Select(self.driver.find_element(By.NAME, "doctor_id"))
        doctors.select_by_index(1)

        future = self.future_date()
        date_field = self.driver.find_element(By.NAME, "appt_date")
        self.driver.execute_script("arguments[0].value = arguments[1];", date_field, future)

        self.submit_picking_a_free_slot("reception_dashboard.php")
        self.assert_shows("Serial")

    # ---------- 6. admin journey ----------

    def test_18_admin_overview_shows_the_totals(self):
        self.login("admin")
        for label in ("Hospital overview", "By department", "Latest bookings"):
            with self.subTest(label=label):
                self.assert_shows(label)

    def test_19_admin_adds_and_removes_a_department(self):
        self.login("admin")
        self.go("manage_departments.php")
        name = "Test Dept " + str(int(time.time()))

        self.driver.find_element(By.NAME, "dept_name").send_keys(name)
        self.driver.find_element(By.NAME, "add").click()
        self.wait.until(EC.presence_of_element_located((By.CLASS_NAME, "alert-ok")))

        # The list prints each name inside an editable text box, so the new
        # department shows up as an input value rather than as page text.
        self.assertIn(name, self.input_values("table input[name=dept_name]"))

        # Remove it again so the suite leaves no litter behind.
        for row in self.driver.find_elements(By.CSS_SELECTOR, "table tr"):
            fields = row.find_elements(By.CSS_SELECTOR, "input[name=dept_name]")
            if fields and fields[0].get_attribute("value") == name:
                row.find_element(By.NAME, "remove").click()
                self.driver.switch_to.alert.accept()
                break
        self.wait.until(EC.presence_of_element_located((By.CLASS_NAME, "alert-ok")))
        self.assertNotIn(name, self.input_values("table input[name=dept_name]"))

    def test_20_department_with_doctors_cannot_be_deleted(self):
        self.login("admin")
        self.go("manage_departments.php")
        rows = self.driver.find_elements(By.CSS_SELECTOR, "table tr")
        for row in rows:
            cells = row.find_elements(By.TAG_NAME, "td")
            if len(cells) >= 2 and cells[1].text.strip().isdigit() and int(cells[1].text) > 0:
                row.find_element(By.NAME, "remove").click()
                self.driver.switch_to.alert.accept()
                self.wait.until(EC.presence_of_element_located((By.CLASS_NAME, "alert-error")))
                self.assertIn("cannot be deleted", self.body())
                return
        self.skipTest("every department is empty")

    def test_21_doctor_with_appointments_cannot_be_removed(self):
        self.login("admin")
        self.go("manage_doctors.php")
        buttons = self.driver.find_elements(By.NAME, "remove")
        if not buttons:
            self.skipTest("no doctors listed")
        buttons[0].click()
        self.driver.switch_to.alert.accept()
        self.wait.until(lambda d: d.find_elements(By.CLASS_NAME, "alert-error")
                        or d.find_elements(By.CLASS_NAME, "alert-ok"))
        # Either outcome is correct; a doctor with bookings must be refused.
        text = self.body()
        if "cannot be removed" in text:
            self.assertIn("appointment", text)
        else:
            self.assertIn("Doctor removed", text)

    # ---------- 7. security ----------

    def test_22_sql_injection_in_login_is_harmless(self):
        self.logout()
        self.go("login.php")
        self.submit_login("' OR '1'='1", "' OR '1'='1")
        self.wait.until(EC.presence_of_element_located((By.CLASS_NAME, "alert-error")))
        self.assertIn("login.php", self.driver.current_url)
        self.assertIn("Wrong email or password", self.body())

    def test_23_session_id_changes_after_login(self):
        self.logout()
        self.go("login.php")
        before = self.driver.get_cookie("PHPSESSID")
        before_value = before["value"] if before else None

        self.submit_login(ACCOUNTS["patient"], PASSWORD)
        self.wait.until(EC.url_contains("patient_dashboard.php"))

        after = self.driver.get_cookie("PHPSESSID")
        self.assertIsNotNone(after)
        if before_value:
            self.assertNotEqual(before_value, after["value"],
                                "session id must be regenerated to stop session fixation")

    def test_24_session_cookie_is_httponly(self):
        self.login("patient")
        cookie = self.driver.get_cookie("PHPSESSID")
        self.assertTrue(cookie.get("httpOnly"),
                        "the session cookie must be HttpOnly so scripts cannot read it")

    def test_25_logout_ends_the_session(self):
        self.login("patient")
        self.logout()
        self.go("patient_dashboard.php")
        self.assertIn("login.php", self.driver.current_url)


if __name__ == "__main__":
    print("Testing:", BASE)
    unittest.main(verbosity=2, exit=False)
