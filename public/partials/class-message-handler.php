<? // Twilio Inbound Message PHP Webhook

$number = $_POST['From'];
$body = $_POST['Body'];

header('Content-Type: text/xml');
?>

<Response>
    <Message>
        Hello <?php echo $number ?>.
        You said <?php echo $body ?>
    </Message>
</Response>