// Load the workshop list from the server without reloading the page

function loadWorkshops() {

    fetch("get_workshops.php")
        .then(res => res.json())
        .then(data => {

            const list = document.getElementById("workshop_list");
            const select = document.getElementById("workshop_id");

            if (data.status === "error") {
                list.innerHTML = "<p class='error'>" + data.message + "</p>";
                return;
            }

            let table = "<table>";
            table += "<tr><th>Title</th><th>Instructor</th><th>Schedule</th><th>Seats</th></tr>";

            let options = "<option value=''>-- Select a workshop --</option>";

            data.workshops.forEach(w => {

                table += "<tr><td>" + w.title + "</td><td>" + w.instructor +
                         "</td><td>" + w.schedule + "</td><td>" + w.seats + "</td></tr>";

                options += "<option value='" + w.id + "'>" + w.title + "</option>";

            });

            table += "</table>";

            list.innerHTML = table;
            select.innerHTML = options;

        })
        .catch(() => {
            document.getElementById("workshop_list").innerHTML =
                "<p class='error'>Could not connect to the server.</p>";
        });
}

// Send the registration form without reloading the page

function registerStudent(event) {

    event.preventDefault();

    const message = document.getElementById("message");

    const formData = new FormData();
    formData.append("student_id", document.getElementById("student_id").value);
    formData.append("name", document.getElementById("name").value);
    formData.append("email", document.getElementById("email").value);
    formData.append("department", document.getElementById("department").value);
    formData.append("workshop_id", document.getElementById("workshop_id").value);

    fetch("register.php", { method: "POST", body: formData })
        .then(res => res.json())
        .then(data => {

            if (data.status === "success") {

                message.innerHTML = "<p class='success'>" + data.message + "</p>";
                document.getElementById("reg_form").reset();

                // Refresh the seat numbers
                loadWorkshops();

            } else {

                message.innerHTML = "<p class='error'>" + data.message + "</p>";

            }

        })
        .catch(() => {
            message.innerHTML = "<p class='error'>Could not connect to the server.</p>";
        });
}

// Run when the page opens
loadWorkshops();

document.getElementById("reg_form").addEventListener("submit", registerStudent);
