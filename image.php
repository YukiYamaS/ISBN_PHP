<?php
session_start();
ini_set('display_errors', 1);

if (isset($_POST["ISBN"])) {
    if (mb_strlen($_POST["ISBN"]) === 13) {
        $_POST["ISBN"] = ISBNTran($_POST["ISBN"]);
    }
    $_SESSION["ISBN"] = $_POST["ISBN"];
// 画像ファイルを取得
    $image_path = file_get_contents("http://images-jp.amazon.com/images/P/" . $_POST["ISBN"] . ".09.MZZZZZZZ", FILE_BINARY);
// 画像ファイルを指定場所に保存
    file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/imageTest/image/tmp.jpg", $image_path);
    $_SESSION['display'] = true;
    header('Location: image.php');
    exit;
}
?>

<?php
function ISBNTran($ISBN)
{
    if (strlen($ISBN) == 10) {
        //ISBN10からISBN13への変換
        $ISBNtmp = "978" . $ISBN;
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $weight = ($i % 2 == 0 ? 1 : 3);
            $sum += (int)substr($ISBNtmp, $i, 1) * (int)$weight;
        }
        //チェックディジットの計算
        $checkDgt = (10 - $sum % 10) == 10 ? 0 : (10 - $sum % 10);
        return "978" . substr($ISBN, 0, 9) . $checkDgt;
    } elseif (strlen($ISBN) == 13) {
        //ISBN13からISBN10への変換
        $ISBNtmp = substr($ISBN, 3, 9);
        $weight = 10;
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int)substr($ISBNtmp, $i, 1) * $weight;
            $weight--;
        }
        //チェックディジットの計算
        if ((11 - $sum % 11) == 11) {
            $checkDgt = 0;
        } elseif ((11 - $sum % 11) == 10) {
            $checkDgt = "X";
        } else {
            $checkDgt = (11 - $sum % 11);
        }
        return substr($ISBN, 3, 9) . $checkDgt;
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<body>
<h1>書籍記録</h1>
<form action="display.php" method="POST">
    <p><input type="submit" value="記録閲覧"></p>
</form>
<br>
<fieldset>
    <legend>ISBN検索登録</legend>
    <form action="image.php" method="POST" enctype="multipart/form-data">
        <p><input type="text" name="ISBN" placeholder="ISBNを入力"></p>
        <input type="submit" value="search">
    </form>
</fieldset>

<br>

<fieldset>
    <legend>手動入力登録</legend>

    <form action="image.php" method="POST" enctype="multipart/form-data">
        <p><input type="text" name="title" size="40" maxlength="20" placeholder="タイトルを入力"></p>
        <input type="file" name="image">　<input type="submit" value="upload">
    </form>
</fieldset>

<?php

if (isset($_FILES["image"]) && is_uploaded_file($_FILES['image']["tmp_name"])) {
    //画像ファイルの指定
    $img_file = $_FILES['image']['tmp_name'];
    //画像ファイルデータを取得
    $img_data = file_get_contents($img_file);
    //MIMEタイプの取得
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_buffer($finfo, $img_data);
    finfo_close($finfo);
    //拡張子の配列（拡張子の種類を増やせば、画像以外のファイルでもOKです）
    $extension_array = array(
        'gif' => 'image/gif',
        'jpg' => 'image/jpeg',
        'png' => 'image/png'
    );
    //MIMEタイプから拡張子を出力
    if ($img_extension = array_search($mime_type, $extension_array, true)) {
        $format = '%s_%s.%s';
        $time = time();
        $sha1 = sha1(uniqid(mt_rand(), true));
        $file_path = sprintf($format, $time, $sha1, $img_extension);
        move_uploaded_file($_FILES['image']['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . "/imageTest/image/" . "tmp." . $img_extension);
        $_SESSION["up"] = true;
    } else {
        echo "拡張子が対象外です";
    }

}
?>

<?php
if (isset($_SESSION["up"]) && $_POST["title"] !== "") {
    echo "<p>" . $_POST["title"] . "</p>";
    echo "<img src=\"http://localhost:8888/imageTest/image/tmp." . $img_extension . "\">";
    session_unset();
    $_SESSION["extension"] = $img_extension;
    $_SESSION["title"] = $_POST["title"];
    echo '<form action="image.php" method="POST">
   <input type="submit" name="save" value="登録">
        <input type="submit" value="リセット">
    </form>';
}

?>

<?php
if (isset($_SESSION["display"])) {
    $data = "https://www.googleapis.com/books/v1/volumes?q=isbn:" . $_SESSION["ISBN"];
    $json = file_get_contents($data);
    $json_decode = json_decode($json, false);
    if (isset($json_decode->items)) {
        $items = $json_decode->items;
        $titles = array();
        foreach ($items as $item) {
            $titles[count($titles)] = $item->volumeInfo->title;
        }
        echo "<p>" . $titles[0] . "</p>";
        $title = $titles[0];
    } else {
        echo "<p>" . "unknown" . "</p>";
        $title = "unknown";
    }

    echo "<img src=\"http://localhost:8888/imageTest/image/tmp.jpg\">";
    session_unset();
    echo '<form action="image.php" method="POST">
   <input type="submit" name="save" value="登録">
        <input type="submit" value="リセット">
    </form>';
    $_SESSION["title"] = $title;
}
?>

<?php
if (isset($_POST["save"])) {
    $extension = "jpg";
    $format = '%s_%s.%s';
    $time = time();
    $sha1 = sha1(uniqid(mt_rand(), true));
    if (isset($_SESSION["extension"])) {
        $extension = $_SESSION["extension"];
    }

    $file_path = sprintf($format, $time, $sha1, $extension);


    if (rename($_SERVER['DOCUMENT_ROOT'] . "/imageTest/image/tmp." . $extension, $_SERVER['DOCUMENT_ROOT'] . "/imageTest/image/" . $file_path)) {
        //データベースの接続設定
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=BOOKS;charset=utf8', 'root', 'root');
        } catch (PDOException $e) {
            exit('データベース接続失敗。' . $e->getMessage());
        }
        $stmt = $pdo->prepare("INSERT INTO image (id,image_path,title) VALUES ('', :image_path, :title)");
        $stmt->bindParam(':image_path', $file_path, PDO::PARAM_STR);
        $stmt->bindValue(':title', $_SESSION["title"], PDO::PARAM_STR);
        $stmt->execute();
        echo "保存できました";
    } else {
        echo '保存できませんでした';
    }
    session_unset();
}
?>


</body>
</html>

