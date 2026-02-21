<?php
$message = "";
$file = "keys.txt";
$current_page = basename($_SERVER['PHP_SELF']);

// Tự động quét tất cả các file trong thư mục (loại trừ file .php và các file hệ thống)
$allowedFiles = [];
$scan = glob("*");
if($scan){
    foreach($scan as $f) {
        // Chỉ lấy file, không lấy file .php, không lấy keys.txt, không lấy .htaccess
        if(is_file($f) && pathinfo($f, PATHINFO_EXTENSION) !== 'php' && $f != $file && $f != ".htaccess") {
            $allowedFiles[] = $f;
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $key = trim($_POST['key']);
    $selectedFile = $_POST['file'];

    if ($key == "") {
        $message = "Vui lòng nhập key!";
    } 
    elseif (!in_array($selectedFile, $allowedFiles)) {
        $message = "File không hợp lệ hoặc không tồn tại!";
    }
    elseif (!file_exists($file)) {
        $message = "Không tìm thấy hệ thống key!";
    } 
    else {

        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $found = false;
        $newData = [];

        foreach ($lines as $line) {

            $parts = explode("|", $line);
            if (count($parts) < 5) continue;

            list($savedKey, $expire, $status, $allowedFile, $uses) = $parts;

            $savedKey = trim($savedKey);
            $expire = trim($expire);
            $status = trim($status);
            $allowedFile = trim($allowedFile);
            $uses = intval($uses);

            if (strtolower($savedKey) === strtolower($key) && $status === "active") {

                if (time() > $expire) {
                    $message = "Key đã hết hạn!";
                    break;
                }

                // kiểm tra file được phép tải
                if ($allowedFile !== "all" && $allowedFile !== $selectedFile) {
                    $message = "Key này không dùng cho file đã chọn!";
                    break;
                }

                if ($uses <= 0) {
                    $message = "Key đã hết lượt sử dụng!";
                    break;
                }

                $uses--; // trừ lượt
                $found = true;

                // Nếu còn lượt thì mới lưu lại vào file, nếu hết lượt (0) thì sẽ bị xóa khỏi file
                if ($uses > 0) {
                    $newData[] = $savedKey."|".$expire."|".$status."|".$allowedFile."|".$uses;
                }

                continue;
            }

            $newData[] = $line;
        }

        if ($found) {
            file_put_contents($file, implode("\n", $newData));
            // Chuyển hướng đến file để tải về
            header("Location: " . $selectedFile);
            exit();
        }

        if ($message == "") {
            $message = "Key không tồn tại hoặc đã bị khóa!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title> LinhMods - Web Tải File </title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body{
    margin:0;
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background: linear-gradient(135deg,#0f0f0f,#1f1f1f);
    font-family:Arial, sans-serif;
}

.box{
    background:#1c1c1c;
    padding:35px;
    border-radius:15px;
    width:330px;
    text-align:center;
    box-shadow:0 0 25px rgba(0,255,150,0.3);
}

h2{
    color:#00ff99;
    margin-bottom:20px;
}

input, select{
    width:100%;
    padding:12px;
    border:none;
    border-radius:8px;
    margin-bottom:15px;
    background:#2b2b2b;
    color:white;
    font-size:14px;
    outline:none;
    text-align:center;
    box-sizing: border-box;
}

input:focus, select:focus{
    box-shadow:0 0 10px #00ff99;
}

button{
    width:100%;
    padding:12px;
    border:none;
    border-radius:8px;
    background:#00ff99;
    color:black;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s;
}

button:hover{
    background:#00cc77;
    box-shadow:0 0 15px #00ff99;
}

.msg{
    margin-bottom:15px;
    color:#ff4d4d;
    font-size:14px;
}

.footer{
    margin-top:15px;
    font-size:12px;
    color:#888;
}
</style>
</head>

<body>

<div class="box">
    <h2>LinhMods</h2>

    <?php if($message != ""){ ?>
        <div class="msg"><?php echo $message; ?></div>
    <?php } ?>

    <form method="POST">
        <input type="text" name="key" placeholder="Nhập key của bạn..." required>

        <select name="file" required>
            <option value="">-- Chọn File Tải --</option>
            <?php 
            if(!empty($allowedFiles)){
                foreach($allowedFiles as $f){
                    // Hiển thị tên file trong danh sách chọn
                    echo "<option value='$f'>$f</option>";
                }
            } else {
                echo "<option value=''>Không có file để tải</option>";
            }
            ?>
        </select>

        <button type="submit">TẢI FILE</button>
    </form>

    <div class="footer">
        Hệ thống tự động cập nhật file • Bảo mật cao
    </div>
</div>

</body>
</html>
