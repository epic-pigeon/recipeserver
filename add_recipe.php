<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add recipe</title>
    <script>
        function query(data, callback) {
            let url = "api.php?";
            for (let prop in data) {
                if (data.hasOwnProperty(prop)) {
                    url += encodeURI(prop) + "=" + encodeURI(data[prop]);
                }
            }
            let xhr = new XMLHttpRequest();
            xhr.open("GET", url);
            xhr.onreadystatechange = function () {
                if(xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
                    if (typeof callback === "function") callback(xhr.responseText);
                }
            };
            xhr.send();
        }
        function createRecipe(name, cooking, callback) {
            query({
                operation: "createRecipe",
                name: name,
                cooking: cooking
            }, callback);
        }
        function addIngredientToRecipe(ingredientID, recipeID, amount, callback) {
            query({
                operation: "addIngredient",
                ingredientID: ingredientID,
                recipeID: recipeID,
                amount: amount
            }, callback);
        }
        window.onload = function () {
            document.getElementById("add_ingredient").onclick = function () {
                let element = document.createElement("li");
                let select = document.getElementById("ingredient");
                element.ingredientID = select.options[select.selectedIndex].value;
                element.amount = parseFloat(document.querySelector("#amount").value);
                element.innerText = select.options[select.selectedIndex].text + " - " + element.amount;
                let button = document.createElement("button");
                button.onclick = function() {
                    document.querySelector("ul").removeChild(element);
                };
                element.appendChild(button);
                document.querySelector("ul").appendChild(element);
            };
            document.getElementById("add_recipe").onclick = function () {
                let name = document.getElementById("name").value;
                let cooking = document.getElementById("cooking").value;
                createRecipe(
                    name,
                    cooking,
                    function (data) {
                        try {
                            let obj = JSON.parse(data);
                            let recipeID = parseInt(obj.last_id);
                            for (let li of document.querySelector("ul").childNodes) {
                                addIngredientToRecipe(li.ingredientID, recipeID, li.amount);
                            }
                        } catch (e) {
                            console.log(e);
                        }
                    }
                )
            }
        }
</script>
</head>
<body>
<label for="name">Name: </label><input type="text" id="name"><br>
<label for="cooking">Cooking: </label><textarea id="cooking"></textarea><br>
<div style="border: 1px solid black">
    <ul id="ingredient_list"></ul>
    Add ingredients:
    <label>Ingredient: </label>
        <select id="ingredient">
            <?php
            $dbc = mysqli_connect("localhost", "recipedb", "IDpkDJIDJ9WDsS0F", "recipedb") or die("failed to connect to db");
            mysqli_set_charset($dbc, 'utf8');
            $result = mysqli_query($dbc, 'SELECT * FROM ingredients');
            while ($row = mysqli_fetch_assoc($result)) {
                echo '<option value="' . $row['ingredient_id'] . '">' . $row['name'] . ' (' . $row['units'] . ')</option>';
            }
            ?>
        </select>
    <label for="amount">Amount: </label><input type="number" id="amount">
    <button id="add_ingredient">Add</button>
</div><br>
<button id="add_recipe">Add recipe</button>
</body>
</html>