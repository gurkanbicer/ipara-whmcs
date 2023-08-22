<?php
    require_once __DIR__ . '/../../../../init.php';

    if (!empty($_POST)) {
        if (!isset($_POST['hash'])) {
            header('HTTP/1.0 400 Bad Request');
            echo "Bad request.";
            exit;
        }

        if (!isset($_POST['orderId'])) {
            header('HTTP/1.0 400 Bad Request');
            echo "Bad request.";
            exit;
        }

        $invoiceId = explode('IPARAM', $_POST['orderId'])[1];
        $errorCode = $_POST['errorCode'] ?? null;
        $errorMessage = $_POST['errorMessage'] ?? null;
        $result = $_POST['result'];
    } else {
        if (isset($_SESSION['IPARAM_PAY_TYPE']) && $_SESSION['IPARAM_PAY_TYPE'] == "API") {
            if (!isset($_SESSION['IPARAM_HASH'])) {
                header('HTTP/1.0 400 Bad Request');
                echo "Bad request.";
                exit;
            }

            if (!isset($_SESSION['IPARAM_ORDER_ID'])) {
                header('HTTP/1.0 400 Bad Request');
                echo "Bad request.";
                exit;
            }

            $invoiceId = explode('IPARAM', $_SESSION['IPARAM_ORDER_ID'])[1];
            $errorCode = $_SESSION['IPARAM_ERROR_CODE'] ?? null;
            $errorMessage = $_SESSION['IPARAM_ERROR_MESSAGE'] ?? null;
            $result = $_SESSION['IPARAM_RESULT'];

        } else {
            header('HTTP/1.0 400 Bad Request');
            echo "Bad request.";
            exit;
        }
    }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/css/bootstrap.min.css"
          integrity="sha512-rt/SrQ4UNIaGfDyEXZtNcyWvQeOq0QLygHluFQcSjaGB04IxWhal71tKuzP6K8eYXYB6vJV4pHkXcmFGGQ1/0w=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <meta name="robots" content="noindex, nofollow">
    <title>Ödeme Başarılı</title>
    <style>
        html, body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body class="bg-light vh-100 d-flex">
    <div class="container align-items-center align-self-center text-center">
        <div class="p-4">
            <p>
                <img src="assets/payment-success.png" alt="Ödeme Başarılı" title="Ödeme Başarılı" width="96" height="96">
            </p>
            <p class="display-4 text-success mb-5">Ödeme Başarılı</p>
            <p class="lead" id="countdown">15 saniye içerisinde müşteri panelinize yönlendirileceksiniz...</p>
            <p class="text-danger">Ödemeniz başarıyla alındı, ancak sisteme işlemesi birkaç dakika alabilir.</p>
            <p><small>Tarayıcınız otomatik yönlendirmeyi desteklemiyorsa butona tıklayarak fatura sayfasına dönebilirsiniz.</small></p>
            <p class="lead mt-4">
                <a href="/viewinvoice.php?id=<?= $invoiceId; ?>" class="btn btn-primary">Faturayı Sayfasını Görüntüle</a>
            </p>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.min.js"
            integrity="sha512-7rusk8kGPFynZWu26OKbTeI+QPoYchtxsmPeBqkHIEXJxeun4yJ4ISYe7C6sz9wdxeE1Gk3VxsIWgCZTc+vX3g=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"
            integrity="sha512-3gJwYpMe3QewGELv8k/BX9vcqhryRdzRMxVfq6ngyWXwo03GFEzjsUm8Q7RZcHPHksttq7/GFoxjCVUjkjvPdw=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script type="text/javascript">
        var invoiceId = "<?= $invoiceId; ?>";
        var countdown = 15;
        var countdownInterval = setInterval(countdownTimer, 1000);

        function countdownTimer() {
            countdown = countdown - 1;
            $('#countdown').html(countdown + ' saniye içerisinde yönlendirileceksiniz...');

            if (countdown === 1) {
                countdownTimerStop();

                setTimeout(function () {
                    window.parent.location.href = "/viewinvoice.php?id=" + invoiceId;
                }, 1000);
            }
        }

        function countdownTimerStop() {
            clearInterval(countdownInterval);
        }
    </script>
</body>
</html>