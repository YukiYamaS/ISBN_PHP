<!DOCTYPE html>
<html lang="ja">
<head>
    <style>
        a.get{
            font-size:0.75em;
            display:block;
            width:50px;
            padding-top:10px;
            padding-bottom:10px;
            text-align:center;
            border:2px solid;
            border-color:#aaaaaa #444444 #444444 #aaaaaa;
            background:#cccccc;
            text-decoration: none;
        }

        a.get:hover{
            background:#777777;
        }
    </style>
</head>
<body>
<h1>記録閲覧</h1>
<form action="image.php" method="POST">
    <p><input type="submit" value="書籍記録"></p>
</form>
<br>
<?php
session_start();
 if(isset($_GET[id])){
         //データベースの接続設定
         try {
             $pdo = new PDO('mysql:host=localhost;dbname=BOOKS;charset=utf8', 'root', 'root');
         } catch (PDOException $e) {
             exit('データベース接続失敗。' . $e->getMessage());
         }

     //$_SERVER['DOCUMENT_ROOT'] . "/imageTest/image/" .
     $sql = 'SELECT * FROM image WHERE id='.$_GET[id];
     $stmt = $pdo -> prepare($sql);
     $stmt -> bindParam(':read_id', $_GET[id], PDO::PARAM_STR);
     $stmt -> execute();
     $result = $stmt->fetch(PDO::FETCH_ASSOC);
     if(isset($result["image_path"])){
         $sql = 'DELETE FROM image WHERE id = :delete_id';
         $stmt = $pdo -> prepare($sql);
         $stmt -> bindParam(':delete_id', $_GET[id], PDO::PARAM_INT);
        if(unlink($_SERVER['DOCUMENT_ROOT'] . "/imageTest/image/" .$result["image_path"])){
            $stmt -> execute();
            echo "消去できました";

        }else{
            echo "消去できませんでした";
        };

     }else{
         echo "消去できませんでした";
     }


 }

?>

<?php
error_reporting(E_ALL & ~E_NOTICE);
//データベースの接続設定
try {
    $pdo = new PDO('mysql:host=localhost;dbname=BOOKS;charset=utf8','root','root');
} catch (PDOException $e) {
    exit('データベース接続失敗。'.$e->getMessage());
}

$stmt = $pdo -> prepare("SELECT * FROM image");
$stmt -> execute();

echo("<table border=\"1\"><tr>");
$count = 0;
while($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if($count != 3){
        echo("<th align=\"center\">".$result["title"].'<p><a class="get" href="display.php?id='.$result["id"].'">書籍削除</a></p>'."</th>");

        echo("<td>");
       echo "<img src=\"http://localhost:8888/imageTest/image/".$result['image_path']."\">";
        echo("</td>");
        $count = $count+1;
    }else{
        echo("</tr><tr>");
        echo("<th align=\"center\">".$result["title"].'<p><a class="get" href="display.php?id='.$result["id"].'">書籍削除</a></p>'."</th>");

        echo("<td>");
        echo "<img src=\"http://localhost:8888/imageTest/image/".$result['image_path']."\">";

        echo("</td>");
        $count = 1;
    }
}
echo("</tr></table>");

?>



</body>
</html>
