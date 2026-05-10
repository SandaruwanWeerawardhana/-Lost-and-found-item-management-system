<?php
include 'db_connect.php';

$results = [];

if(isset($_POST['search'])) {

    $keyword = $_POST['keyword'];

    $sql = "SELECT * FROM lost_items
            WHERE item_name LIKE :keyword
            OR reported_by LIKE :keyword";

    $stmt = $conn->prepare($sql);

    $searchTerm = "%" . $keyword . "%";

    $stmt->execute([
        ':keyword' => $searchTerm
    ]);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {

    // Show all records at first load

    $sql = "SELECT * FROM lost_items";

    $stmt = $conn->prepare($sql);

    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>

    <title>Search and View Lost Items</title>

    <style>

        body{
            font-family: Arial;
            margin: 30px;
        }

        table{
            width: 100%;
            border-collapse: collapse;
        }

        table, th, td{
            border: 1px solid black;
        }

        th, td{
            padding: 10px;
            text-align: center;
        }

        input{
            padding: 8px;
            width: 250px;
        }

        button{
            padding: 8px 15px;
        }

        a{
            text-decoration: none;
            color: blue;
        }

    </style>

</head>

<body>

<h2>Search Lost Items</h2>

<form method="POST">

    <input type="text"
           name="keyword"
           placeholder="Search by Item Number or Reported By">

    <button type="submit" name="search">
        Search
    </button>

</form>

<br>

<table>

    <tr>

        <th>ID</th>
        <th>Item Name</th>
        <th>Category</th>
        <th>Location</th>
        <th>Date Found</th>
        <th>Reported By</th>
        <th>Contact</th>
        <th>Status</th>
        <th>Action</th>

    </tr>

    <?php

    if(count($results) > 0){

        foreach($results as $row){

    ?>

    <tr>

        <td><?php echo htmlspecialchars($row['id']); ?></td>

        <td><?php echo htmlspecialchars($row['item_name']); ?></td>

        <td><?php echo htmlspecialchars($row['category']); ?></td>

        <td><?php echo $row['location_found']; ?></td>

        <td><?php echo $row['date_found']; ?></td>

        <td><?php echo $row['reported_by']; ?></td>

        <td><?php echo $row['contact_number']; ?></td>

        <td><?php echo $row['status']; ?></td>

        <td>

            <a href="edit.php?id=<?php echo $row['id']; ?>">
                Edit
            </a>

        </td>

    </tr>

    <?php

        }

    } else {

    ?>

    <tr>

        <td colspan="10">
            No records found
        </td>

    </tr>

    <?php
    }
    ?>

</table>

</body>
</html>