const form = document.getElementById("contactform");
form.addEventListener("submit",function(event)
{
    event.preventDefault();
    const name = document.getElementById("Name").value.trim();
    const email = document.getElementById("Email").value.trim();
    const subject = document.getElementById("Subject").value;
    const message = document.getElementById("Message").value;
    if(message === "")
    {
        alert("Please ! Write a message.");
        return;
    }
    alert("Message sent Successfully!!");
    form.reset();
});
