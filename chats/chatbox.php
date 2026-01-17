<div id="chat-box" style="height:300px; overflow-y:auto; border:1px solid #ccc; padding:10px;"></div>

<input type="hidden" id="receiver_id" value="<?= $receiver_id ?>">

<textarea id="message"></textarea>
<button onclick="sendMessage()">Send</button>

<script>
function loadMessages() {
    let receiver = document.getElementById('receiver_id').value;

    fetch("fetch.php?u1=<?= $_SESSION['user_id'] ?>&u2=" + receiver)
    .then(res => res.json())
    .then(data => {
        let html = "";
        data.forEach(msg => {
            html += "<p><strong>" + msg.sender_id + ":</strong> " + msg.message + "</p>";
        });
        document.getElementById("chat-box").innerHTML = html;
    });
}

setInterval(loadMessages, 1500); // updates every 1.5 sec

function sendMessage() {
    let receiver = document.getElementById('receiver_id').value;
    let message = document.getElementById('message').value;

    fetch("send.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "receiver_id=" + receiver + "&message=" + encodeURIComponent(message)
    }).then(() => {
        document.getElementById('message').value = "";
        loadMessages();
    });
}
</script>
