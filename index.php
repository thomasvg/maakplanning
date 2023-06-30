<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start a new session or resume an existing one
session_start();

// Connect to the database using a separate user with limited privileges
$connect = mysqli_connect("localhost", "root", "", "lotus");

// Check if the delete button has been clicked
if (isset($_POST['delete'])) {
    // Get the ID of the row to delete from the form data
    $delete_id = $_POST['delete_id'];
    // Use a prepared statement with a parameterized query to prevent SQL injection
    $query = "DELETE FROM planning WHERE id = ?";
    $stmt = mysqli_prepare($connect, $query);
    mysqli_stmt_bind_param($stmt, 'i', $delete_id);
    mysqli_stmt_execute($stmt);
}

// Use a prepared statement with a parameterized query to prevent SQL injection
$query = "SELECT * FROM locaties";
$stmt = mysqli_prepare($connect, $query);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch all locations from the database and store them in an array
$loc = [];
while ($row = mysqli_fetch_array($result)) {
    $loc[] = $row;
}

// Check if the form has been submitted
if (isset($_POST["submit"])) {
    // Check if the location has been selected
    if (isset($_POST["locatie"])) {
        // Split the location and ID values
        list($locatie, $id) = explode(',', $_POST["locatie"]);
        // Store the location and ID values in session variables
        $_SESSION['locatie'] = $locatie;
        $_SESSION['id'] = $id;
    }
}

// Check if the location has been set and if any of the form fields have been submitted
if (isset($_SESSION['locatie']) && (isset($_POST['locatie']) || isset($_POST['ruimte']) || isset($_POST['werknemer_id']) || isset($_POST['enum']))) {
    // Use a prepared statement with a parameterized query to prevent SQL injection
    $query2 = "SELECT * FROM ruimtes WHERE locatie_id = ?";
    $stmt = mysqli_prepare($connect, $query2);
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    $result2 = mysqli_stmt_get_result($stmt);

    // Fetch all rooms for the selected location and store them in an array
    $ruimtes = [];
    while ($row2 = mysqli_fetch_array($result2)) {
        $ruimtes[] = $row2;
    }
}

// Check if the room has been selected
if (isset($_POST['ruimte'])) {
    // Store the selected room ID in a session variable
    $_SESSION['ruimte_id'] = $_POST['ruimte'];
}

// Check if the room ID has been set and if the rooms array is not empty
if (isset($_SESSION['ruimte_id']) && isset($ruimtes)) {
    // Find the index of the selected room in the rooms array
    $index = array_search($_SESSION['ruimte_id'], array_column($ruimtes, 'id'));
    // Check if the index was found
    if ($index !== false) {
        // Store the room name and ID in session variables
        $_SESSION['ruimte'] = $ruimtes[$index]['ruimte'];
        $_SESSION['ruimte_id'] = $ruimtes[$index]['id'];
    }
}

// Check if the room ID has been set
if (isset($_SESSION['ruimte_id'])) {
    // Get the room ID from the session variable
    $ruimte_id = $_SESSION['ruimte_id'];
    // Use a prepared statement with a parameterized query to prevent SQL injection
    $query3 = "SELECT werknemer_id, initialen FROM werknemer_ruimtes INNER JOIN operatoren ON werknemer_ruimtes.werknemer_id = operatoren.id WHERE ruimte_id = ?";
    $stmt = mysqli_prepare($connect, $query3);
    mysqli_stmt_bind_param($stmt, 'i', $ruimte_id);
    mysqli_stmt_execute($stmt);
    $result3 = mysqli_stmt_get_result($stmt);

    // Fetch all employees for the selected room and store them in an array
    $werknemers = [];
    while ($row3 = mysqli_fetch_array($result3)) {
        $werknemers[] = $row3;
    }
}

// Check if an employee has been selected
if (isset($_POST['werknemer_id'])) {
    // Store the selected employee ID in a session variable
    $_SESSION['werknemer_id'] = $_POST['werknemer_id'];
}

// Check if the employee ID has been set
if (isset($_SESSION['werknemer_id'])) {
    // Get the employee ID from the session variable
    $werknemer_id = $_SESSION['werknemer_id'];
    // Use a prepared statement with a parameterized query to prevent SQL injection
    $query4 = "SELECT initialen FROM operatoren WHERE id = ?";
    $stmt = mysqli_prepare($connect, $query4);
    mysqli_stmt_bind_param($stmt, 'i', $werknemer_id);
    mysqli_stmt_execute($stmt);
    $result4 = mysqli_stmt_get_result($stmt);
    // Fetch the employee's initials from the database
    $initialen = mysqli_fetch_array($result4)['initialen'];
}

// Check if the "delete session" button has been clicked
if (isset($_POST['deletesession'])) {
    // Destroy the current session and redirect to the index page
    session_destroy();
    header("location: index.php");
    exit;
}

// Check if the date form has been submitted
if (isset($_POST['submitdate'])) {
    // Get the selected date from the form data
    $date = $_POST['date'];
    // Store the selected date in a session variable
    $_SESSION['date'] = $date;
}

// Check if the "dag" or "nacht" value has been submitted
if (isset($_POST['enum'])) {
    // Store the selected value in a session variable
    $_SESSION['enum'] = $_POST['enum'];
}

// Check if all the required variables are set and if the submitTable button has been clicked
if (isset($_SESSION['date']) && isset($_SESSION['locatie']) && isset($_SESSION['ruimte']) && isset($initialen) && isset($_SESSION['enum']) && isset($_POST['submitTable'])) {
    // Get the values from the session variables
    $date = $_SESSION['date'];
    $locatie = $_SESSION['locatie'];
    $ruimte = $_SESSION['ruimte'];
    $shift = $_SESSION['enum'];



    // Use a prepared statement with a parameterized query to prevent SQL injection
    $query = "INSERT INTO planning (date, locatie, ruimte, initialen, shift) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($connect, $query);
    if ($stmt === false) {
        // There was an error when preparing the SQL statement
        die(mysqli_error($connect));
    }
    mysqli_stmt_bind_param($stmt, 'sssss', $date, $locatie, $ruimte, $initialen, $shift);
    mysqli_stmt_execute($stmt);

    // Check if any rows were affected
    if (mysqli_stmt_affected_rows($stmt) > 0) {

        echo "<script>alert('succesvol!');</script>";

        if (isset($_POST['submitTable'])) {
            $_SESSION = [];
        }
    } else {
        // There was an error when inserting the data
        echo "Error inserting data: " . mysqli_error($connect);
    }
}




function check_data_exists($date, $location, $ruimte, $shift)
{
    $connect = mysqli_connect("localhost", "root", "", "lotus");
    $query = "SELECT * FROM planning WHERE date = ? AND locatie = ? AND ruimte = ? AND shift = ?";
    $stmt = mysqli_prepare($connect, $query);
    mysqli_stmt_bind_param($stmt, 'ssss', $date, $location, $ruimte, $shift);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    return mysqli_stmt_num_rows($stmt) > 0;
}

if (isset($_SESSION['date'], $_SESSION['locatie'], $_SESSION['ruimte'], $_SESSION['enum'])) {
    if (check_data_exists($_SESSION['date'], $_SESSION['locatie'], $_SESSION['ruimte'], $_SESSION['enum'])) {
        echo "<script>
            if (confirm('data bestaat al, start opnieuw!!')) {
                
            }
        </script>";
        $_SESSION = [];
    }
}



function get_titles()
{
    $names = [];
    $connect = mysqli_connect("localhost", "root", "", "lotus");
    $query_planning = "SHOW COLUMNS FROM planning";

    $result_planning = mysqli_query($connect, $query_planning);

    while ($row = mysqli_fetch_array($result_planning)) {
        $names[] = $row['Field'];
    }
    return $names;
}

$names = get_titles();

function get_data($date)
{
    $data = [];
    $connect = mysqli_connect("localhost", "root", "", "lotus");
    $query_planning = "SELECT * FROM planning WHERE date = ?";
    $stmt = mysqli_prepare($connect, $query_planning);
    mysqli_stmt_bind_param($stmt, 's', $date);
    mysqli_stmt_execute($stmt);
    $result_planning = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result_planning)) {
        $data[] = $row;
    }
    return $data;
}

// Check if the filter form has been submitted
if (isset($_POST['filter'])) {
    // Get the filter date from the form data
    $filter_date = $_POST['filter_date'];
    // Store the filter date in a session variable
    $_SESSION['filter_date'] = $filter_date;
}

// Check if the filter date is set in the session
if (isset($_SESSION['filter_date'])) {
    // Get the filter date from the session variable
    $filter_date = $_SESSION['filter_date'];
    // Get the data for the selected date
    $data = get_data($filter_date);
}





?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="style.css" rel="stylesheet">
    <title>Document</title>
</head>

<body>

    <div class="form-wrap">

        <div class="planning-wrap">
            <div class="title">
                <h2>Maak planning</h2>
            </div>

            <!-- Date selection form -->
            <form method="post" id="dateForm">
                <input type="date" name="date" id="dateInput" value="<?= isset($_SESSION['date']) ? htmlspecialchars($_SESSION['date']) : '' ?>" />
                <input type="submit" name="submitdate" value="Registreer" />
            </form>



            <!-- Location selection form -->
            <?php if (isset($_SESSION['date'])) : ?>
                <form method="post" id="locationForm">
                    <select name="locatie" id="locatie">
                        <option selected disabled>selecteer</option>
                        <?php foreach ($loc as $location) : ?>
                            <option value="<?= htmlspecialchars($location["locatie"]) ?>,<?= htmlspecialchars($location["id"]) ?>" <?= (isset($_SESSION['locatie']) && $_SESSION['locatie'] == $location["locatie"]) ? 'selected' : '' ?>><?= htmlspecialchars($location["locatie"]) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" name="submit" value="Registreer" />
                </form>
            <?php endif; ?>

            <!-- Room selection form -->
            <?php if (isset($_SESSION['locatie']) && (isset($_POST['locatie']) || isset($_POST['ruimte']) || isset($_POST['werknemer_id']) || isset($_POST['enum']))) : ?>
                <form method="post">
                    <select name="ruimte" id="ruimte">
                        <option value="selecteer ruimte" selected disabled>selecteer</option>
                        <?php foreach ($ruimtes as $ruimte) : ?>
                            <option value="<?= htmlspecialchars($ruimte["id"]) ?>" <?= (isset($_SESSION['ruimte_id']) && $_SESSION['ruimte_id'] == $ruimte["id"]) ? 'selected' : '' ?>><?= htmlspecialchars($ruimte["ruimte"]) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" value="Registreer" />
                </form>
            <?php endif; ?>

            <!-- Employee selection form -->
            <?php if (isset($_SESSION['ruimte_id'])) : ?>
                <form method="post">
                    <select name="werknemer_id" id="werknemer_id">
                        <option value="selecteer werknemer" selected disabled>selecteer</option>
                        <?php foreach ($werknemers as $werknemer) : ?>
                            <option value="<?= htmlspecialchars($werknemer["werknemer_id"]) ?>" <?= (isset($_SESSION['werknemer_id']) && $_SESSION['werknemer_id'] == $werknemer["werknemer_id"]) ? 'selected' : '' ?>><?= htmlspecialchars($werknemer["initialen"]) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" value="Registreer" />
                </form>
            <?php endif; ?>

            <!-- "Dag" or "Nacht" selection form -->
            <?php if (isset($_SESSION['werknemer_id'])) : ?>
                <form method="post">
                    <select name="enum" id="enum">
                        <option value="" disabled>selecteer</option>
                        <option value="dag" <?= (isset($_SESSION['enum']) && $_SESSION['enum'] == 'dag') ? 'selected' : '' ?>>dag</option>
                        <option value="nacht" <?= (isset($_SESSION['enum']) && $_SESSION['enum'] == 'nacht') ? 'selected' : '' ?>>nacht</option>
                    </select>
                    <input type="submit" value="Registreer" />
                </form>
            <?php endif; ?>



            <!-- Display the selected values in a table -->
            <?php if (isset($_SESSION['locatie']) && isset($_SESSION['ruimte']) && isset($_SESSION['date']) && isset($_SESSION['werknemer_id']) && isset($_SESSION['enum'])) : ?>
                <table>
                    <tr>
                        <th>Datum</th>
                        <th>Locatie</th>
                        <th>Ruimte</th>
                        <th>Initialen</th>
                        <th>Dag/Nacht</th>
                    </tr>
                    <tr>
                        <td><?= htmlspecialchars($_SESSION['date']) ?></td>
                        <td><?= htmlspecialchars($_SESSION['locatie']) ?></td>
                        <td><?= htmlspecialchars($_SESSION['ruimte']) ?></td>
                        <td><?= htmlspecialchars($initialen) ?></td>
                        <td><?= htmlspecialchars($_SESSION['enum']) ?></td>

                    </tr>
                </table>

                <!-- Submit button form -->
                <form method="post">
                    <input type="submit" name="submitTable" value="Submit">
                </form>

                <!-- "Delete session" button -->
                <form method="post">
                    <input type="submit" name="deletesession" value="verwijder schema">
                </form>
            <?php endif; ?>

            <?php if (!empty($data)) : ?>
                <table>
                    <thead>
                        <tr>
                            <?php foreach ($names as $name) : ?>
                                <th><?= htmlspecialchars($name) ?></th>
                            <?php endforeach; ?>
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row) : ?>
                            <tr>
                                <?php foreach ($row as $value) : ?>
                                    <td><?= htmlspecialchars($value) ?></td>
                                <?php endforeach; ?>
                                <td>
                                    <form method="post">
                                        <input type="hidden" name="delete_id" value="<?= htmlspecialchars($row['id']) ?>">
                                        <input type="submit" name="delete" value="Delete">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>



            <?php endif; ?>

        </div>
        <div class="planning-wrap">
            <!-- Date filter form -->

            <div class="title">
                <h2>Filter & verwijder</h2>
            </div>
            <form method="post">




                <input type="date" name="filter_date" />
                <input type="submit" name="filter" value="Registreer" />
            </form>
        </div>
    </div>



    <!-- Form validation script -->
    <script>
        document.getElementById('dateForm').addEventListener('submit', function(event) {
            var dateInput = document.getElementById('dateInput');
            if (!dateInput.value) {
                event.preventDefault();
                alert('Please select a date before submitting the form.');
            }
        });

        document.getElementById('locationForm').addEventListener('submit', function(event) {
            var locationSelect = document.getElementById('locatie');
            if (locationSelect.value === 'selecteer') {
                event.preventDefault();
                alert('Please select a location before submitting the form.');
            }
        });
    </script>
</body>

</html>