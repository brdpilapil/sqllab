<?php
declare(strict_types=1);

// =====================================================
// index.php  -  the page (HTML + forms) and the code that
//               runs when a button is pressed
// =====================================================

require "functions.php";

$message = "";   // green message (success)
$error = "";     // red message (something is wrong)

// Message sent back after a successful action  (index.php?msg=...)
if (isset($_GET["msg"])) {
    $message = $_GET["msg"];
}

// What the two forms show at the start (empty = "add" mode)
$form_author = ["id" => "", "name" => "", "email" => "", "bio" => ""];
$form_book = ["id" => "", "title" => "", "author_id" => "", "isbn" => "", "published_year" => ""];


// -----------------------------------------------------
// PART 1: Edit buttons  (they open the page with ?edit_author=3 or ?edit_book=5)
// -----------------------------------------------------
if (isset($_GET["edit_author"])) {
    $row = get_author((int) $_GET["edit_author"]);
    if ($row) {
        $form_author = $row;
        $form_author["bio"] = $row["bio"] ?? "";
    }
}

if (isset($_GET["edit_book"])) {
    $row = get_book((int) $_GET["edit_book"]);
    if ($row) {
        $form_book = $row;
        $form_book["isbn"] = $row["isbn"] ?? "";
        $form_book["published_year"] = $row["published_year"] ?? "";
    }
}


// -----------------------------------------------------
// PART 2: A form button was pressed (POST)
// -----------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";

    // ---------- AUTHORS ----------

    if ($action === "add_author" || $action === "update_author") {
        $id = (int) ($_POST["id"] ?? 0);
        $name = trim($_POST["name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $bio = trim($_POST["bio"] ?? "");

        $error = validate_author($name, $email);

        if ($error === "") {
            try {
                if ($action === "add_author") {
                    add_author($name, $email, $bio);
                    $done = "Author added.";
                } else {
                    update_author($id, $name, $email, $bio);
                    $done = "Author updated.";
                }
                header("Location: index.php?msg=" . urlencode($done));
                exit;
            } catch (PDOException $e) {
                $error = "That email is already used by another author.";
            }
        }

        // something went wrong: keep what the user typed in the form
        $form_author = ["id" => ($id > 0 ? $id : ""), "name" => $name, "email" => $email, "bio" => $bio];
    }

    if ($action === "delete_author") {
        $id = (int) ($_POST["id"] ?? 0);
        $count = count_books_of_author($id);

        if ($count > 0) {
            $error = "Can't delete this author: they still have $count book(s). Delete those books first.";
        } else {
            delete_author($id);
            header("Location: index.php?msg=" . urlencode("Author deleted."));
            exit;
        }
    }

    // ---------- BOOKS ----------

    if ($action === "add_book" || $action === "update_book") {
        $id = (int) ($_POST["id"] ?? 0);
        $title = trim($_POST["title"] ?? "");
        $author_id = (int) ($_POST["author_id"] ?? 0);
        $isbn = trim($_POST["isbn"] ?? "");
        $year = trim($_POST["published_year"] ?? "");

        $error = validate_book($title, $author_id, $isbn, $year);

        if ($error === "") {
            try {
                if ($action === "add_book") {
                    add_book($title, $author_id, $isbn, $year);
                    $done = "Book added.";
                } else {
                    update_book($id, $title, $author_id, $isbn, $year);
                    $done = "Book updated.";
                }
                header("Location: index.php?msg=" . urlencode($done));
                exit;
            } catch (PDOException $e) {
                $error = "That ISBN is already used by another book (or the author does not exist).";
            }
        }

        // something went wrong: keep what the user typed in the form
        $form_book = ["id" => ($id > 0 ? $id : ""), "title" => $title, "author_id" => $author_id, "isbn" => $isbn, "published_year" => $year];
    }

    if ($action === "delete_book") {
        delete_book((int) ($_POST["id"] ?? 0));
        header("Location: index.php?msg=" . urlencode("Book deleted."));
        exit;
    }
}


// -----------------------------------------------------
// PART 3: Get the rows we want to show in the tables
// -----------------------------------------------------
$authors = get_all_authors();
$books = get_all_books();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Authors and Books</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">

    <h1>Authors and Books</h1>

    <?php
    // show the green / red message if there is one
    if ($message !== "") {
        echo "<div class='success'>" . h($message) . "</div>";
    }
    if ($error !== "") {
        echo "<div class='error'>" . h($error) . "</div>";
    }
    ?>


    <!-- ===================== AUTHORS ===================== -->
    <h2 id="authors">Authors</h2>

    <div class="box">
        <?php
        // if the form has an id we are editing, otherwise we are adding
        if ($form_author["id"] !== "") {
            echo "<h3>Edit author</h3>";
        } else {
            echo "<h3>Add author</h3>";
        }
        ?>

        <form method="post" action="index.php#authors">
            <?php
            if ($form_author["id"] !== "") {
                echo "<input type='hidden' name='action' value='update_author'>";
                echo "<input type='hidden' name='id' value='" . h($form_author["id"]) . "'>";
            } else {
                echo "<input type='hidden' name='action' value='add_author'>";
            }
            ?>

            <label>Name</label>
            <input type="text" name="name" maxlength="120" value="<?php echo h($form_author["name"]); ?>">

            <label>Email</label>
            <input type="email" name="email" maxlength="190" value="<?php echo h($form_author["email"]); ?>">

            <label>Bio (optional)</label>
            <textarea name="bio" rows="3"><?php echo h($form_author["bio"]); ?></textarea>

            <?php
            if ($form_author["id"] !== "") {
                echo "<button type='submit'>Update author</button> ";
                echo "<a class='button button-gray' href='index.php#authors'>Cancel</a>";
            } else {
                echo "<button type='submit'>Add author</button>";
            }
            ?>
        </form>
    </div>

    <table>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Books</th>
            <th>Actions</th>
        </tr>
        <?php
        foreach ($authors as $author) {
            echo "<tr>";
            echo "<td>" . h($author["name"]) . "</td>";
            echo "<td>" . h($author["email"]) . "</td>";
            echo "<td>" . h($author["book_count"]) . "</td>";
            echo "<td>";

            // EDIT button (a link that opens the page in edit mode)
            echo "<a class='button small' href='index.php?edit_author=" . h($author["id"]) . "#authors'>Edit</a> ";

            // DELETE button (a tiny form)
            echo "<form method='post' action='index.php' onsubmit=\"return confirm('Delete this author?');\">";
            echo "<input type='hidden' name='action' value='delete_author'>";
            echo "<input type='hidden' name='id' value='" . h($author["id"]) . "'>";
            echo "<button type='submit' class='button-red small'>Delete</button>";
            echo "</form>";

            echo "</td>";
            echo "</tr>";
        }

        if (count($authors) === 0) {
            echo "<tr><td colspan='4'>No authors yet.</td></tr>";
        }
        ?>
    </table>


    <!-- ===================== BOOKS ===================== -->
    <h2 id="books">Books</h2>

    <div class="box">
        <?php
        if ($form_book["id"] !== "") {
            echo "<h3>Edit book</h3>";
        } else {
            echo "<h3>Add book</h3>";
        }
        ?>

        <form method="post" action="index.php#books">
            <?php
            if ($form_book["id"] !== "") {
                echo "<input type='hidden' name='action' value='update_book'>";
                echo "<input type='hidden' name='id' value='" . h($form_book["id"]) . "'>";
            } else {
                echo "<input type='hidden' name='action' value='add_book'>";
            }
            ?>

            <label>Title</label>
            <input type="text" name="title" maxlength="200" value="<?php echo h($form_book["title"]); ?>">

            <label>Author</label>
            <select name="author_id">
                <option value="0">-- Choose an author --</option>
                <?php
                // one <option> for every author in the database
                foreach ($authors as $author) {
                    $selected = "";
                    if ((string) $author["id"] === (string) $form_book["author_id"]) {
                        $selected = "selected";
                    }
                    echo "<option value='" . h($author["id"]) . "' $selected>" . h($author["name"]) . "</option>";
                }
                ?>
            </select>

            <label>ISBN (optional)</label>
            <input type="text" name="isbn" maxlength="20" value="<?php echo h($form_book["isbn"]); ?>">

            <label>Published year (optional)</label>
            <input type="number" name="published_year" value="<?php echo h($form_book["published_year"]); ?>">

            <?php
            if ($form_book["id"] !== "") {
                echo "<button type='submit'>Update book</button> ";
                echo "<a class='button button-gray' href='index.php#books'>Cancel</a>";
            } else {
                echo "<button type='submit'>Add book</button>";
            }
            ?>
        </form>
    </div>

    <table>
        <tr>
            <th>Title</th>
            <th>Author</th>
            <th>ISBN</th>
            <th>Year</th>
            <th>Actions</th>
        </tr>
        <?php
        foreach ($books as $book) {
            echo "<tr>";
            echo "<td>" . h($book["title"]) . "</td>";
            echo "<td>" . h($book["author_name"]) . "</td>";
            echo "<td>" . h($book["isbn"]) . "</td>";
            echo "<td>" . h($book["published_year"]) . "</td>";
            echo "<td>";

            echo "<a class='button small' href='index.php?edit_book=" . h($book["id"]) . "#books'>Edit</a> ";

            echo "<form method='post' action='index.php' onsubmit=\"return confirm('Delete this book?');\">";
            echo "<input type='hidden' name='action' value='delete_book'>";
            echo "<input type='hidden' name='id' value='" . h($book["id"]) . "'>";
            echo "<button type='submit' class='button-red small'>Delete</button>";
            echo "</form>";

            echo "</td>";
            echo "</tr>";
        }

        if (count($books) === 0) {
            echo "<tr><td colspan='5'>No books yet.</td></tr>";
        }
        ?>
    </table>

</div>
</body>
</html>
