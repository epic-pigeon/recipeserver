<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add recipe</title>
</head>
<body>
<label for="name">Name: </label><input type="text" id="name">
<label for="cooking">Cooking: </label><textarea id="cooking"></textarea>
<div>
    <ul id="ingredient_list"></ul>
    Add ingredients:
    <label>Ingredient: </label>
        <select id="ingredient">
            <?php
            $dbc = mysqli_connect("localhost", "recipedb", "IDpkDJIDJ9WDsS0F", "recipedb") or die("failed to connect to db");
            mysqli_set_charset($dbc, 'utf8');
            $result = mysqli_query($dbc, 'SELECT * FROM ingredients');
            while ($row = mysqli_fetch_row($result)) {
                echo '<option id="' . $row['ingredient_id'] . '">' . $row['name'] . ' (' . $row['units'] . ')</option>';
            }
            ?>
        </select>
    <label for="amount">Amount: </label><input type="text" id="amount">
    <button id="add_ingredient">Add</button>
</div>
</body>
</html>