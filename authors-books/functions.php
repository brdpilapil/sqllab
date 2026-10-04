<?php
declare(strict_types=1);

// =====================================================
// functions.php  -  database connection + all CRUD functions
// =====================================================


// ---------- 1. CONNECT TO THE DATABASE ----------
// These are XAMPP's default settings.
function connect_db()
{
    $host = "localhost";
    $dbname = "library";
    $username = "root";
    $password = "";

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);

    // show real errors, and give us rows as arrays like $row['name']
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $pdo;
}


// ---------- 2. SMALL HELPERS ----------

// Makes text safe to print inside HTML (stops XSS)
function h($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, "UTF-8");
}

// Checks the author form. Returns an error message, or "" if everything is OK.
function validate_author($name, $email)
{
    if ($name === "") {
        return "Name is required.";
    }
    if ($email === "") {
        return "Email is required.";
    }
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return "Email is not a valid email address.";
    }
    return "";
}

// Checks the book form. Returns an error message, or "" if everything is OK.
function validate_book($title, $author_id, $isbn, $year)
{
    if ($title === "") {
        return "Title is required.";
    }
    if ($author_id === 0) {
        return "Please choose an author.";
    }
    if ($year !== "") {
        if (!ctype_digit($year) || (int) $year < 1000 || (int) $year > (int) date("Y") + 1) {
            return "Published year must be a valid year (example: 1999).";
        }
    }
    return "";
}


// ---------- 3. AUTHORS: CREATE / READ / UPDATE / DELETE ----------

// CREATE
function add_author($name, $email, $bio)
{
    $pdo = connect_db();
    $sql = "INSERT INTO authors (name, email, bio) VALUES (:name, :email, :bio)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":name" => $name,
        ":email" => $email,
        ":bio" => ($bio === "" ? null : $bio),   // empty bio is saved as NULL
    ]);
}

// READ (all authors, with how many books each one has)
function get_all_authors()
{
    $pdo = connect_db();
    $sql = "SELECT authors.id, authors.name, authors.email, authors.bio,
                   COUNT(books.id) AS book_count
            FROM authors
            LEFT JOIN books ON books.author_id = authors.id
            GROUP BY authors.id, authors.name, authors.email, authors.bio
            ORDER BY authors.name";
    return $pdo->query($sql)->fetchAll();
}

// READ (one author by id). Returns false if not found.
function get_author($id)
{
    $pdo = connect_db();
    $stmt = $pdo->prepare("SELECT * FROM authors WHERE id = :id");
    $stmt->execute([":id" => $id]);
    return $stmt->fetch();
}

// UPDATE
function update_author($id, $name, $email, $bio)
{
    $pdo = connect_db();
    $sql = "UPDATE authors SET name = :name, email = :email, bio = :bio WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":name" => $name,
        ":email" => $email,
        ":bio" => ($bio === "" ? null : $bio),
        ":id" => $id,
    ]);
}

// DELETE
function delete_author($id)
{
    $pdo = connect_db();
    $stmt = $pdo->prepare("DELETE FROM authors WHERE id = :id");
    $stmt->execute([":id" => $id]);
}

// How many books does this author have? (we can't delete an author who still has books)
function count_books_of_author($author_id)
{
    $pdo = connect_db();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE author_id = :id");
    $stmt->execute([":id" => $author_id]);
    return (int) $stmt->fetchColumn();
}


// ---------- 4. BOOKS: CREATE / READ / UPDATE / DELETE ----------

// CREATE
function add_book($title, $author_id, $isbn, $year)
{
    $pdo = connect_db();
    $sql = "INSERT INTO books (title, author_id, isbn, published_year)
            VALUES (:title, :author_id, :isbn, :year)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":title" => $title,
        ":author_id" => $author_id,
        ":isbn" => ($isbn === "" ? null : $isbn),
        ":year" => ($year === "" ? null : (int) $year),
    ]);
}

// READ (all books, with the author's name using a JOIN)
function get_all_books()
{
    $pdo = connect_db();
    $sql = "SELECT books.id, books.title, books.author_id, books.isbn,
                   books.published_year, authors.name AS author_name
            FROM books
            INNER JOIN authors ON books.author_id = authors.id
            ORDER BY books.title";
    return $pdo->query($sql)->fetchAll();
}

// READ (one book by id). Returns false if not found.
function get_book($id)
{
    $pdo = connect_db();
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = :id");
    $stmt->execute([":id" => $id]);
    return $stmt->fetch();
}

// UPDATE
function update_book($id, $title, $author_id, $isbn, $year)
{
    $pdo = connect_db();
    $sql = "UPDATE books
            SET title = :title, author_id = :author_id, isbn = :isbn, published_year = :year
            WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":title" => $title,
        ":author_id" => $author_id,
        ":isbn" => ($isbn === "" ? null : $isbn),
        ":year" => ($year === "" ? null : (int) $year),
        ":id" => $id,
    ]);
}

// DELETE
function delete_book($id)
{
    $pdo = connect_db();
    $stmt = $pdo->prepare("DELETE FROM books WHERE id = :id");
    $stmt->execute([":id" => $id]);
}
