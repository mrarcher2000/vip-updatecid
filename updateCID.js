const numberDropdown = document.querySelector("#numberDropdown");
const message = document.querySelector("#message");
const tempAccessElement = document.querySelector("#tempAccessElement");

// var access = "";
// Test if webpage is loading button elements properly
numberDropdown.addEventListener("click", function(e){
    e.preventDefault();

    console.log("Button Clicked!");
});

const updateNumber = async function (user, name, number) {
    console.log("Update pending..." + `\n${user}, ${name}, ${number}`);
    let response = await fetch(`./updateCID.php/?uid=${user}&callid_name=${name}&callid_nmbr=${number}`, {method: "POST", headers: {"Content-Type": "application/x-www-form-urlencoded"}})
    // .then((response) => response.json())
    .then((response) => {
        console.log(response);
        if (response.status == 202){
            console.log("Caller ID Updated! \n" + `${name} \n${number}`);
            numberDropdown.innerHTML = number;

            message.style = "color: green;";
            message.innerHTML = "Caller ID Updated!";
        } else {
            console.log(response.status);
            message.style="color: red;";
            message.innerHTML = "An error occurred! Please refresh and try again.";
        }
    });
}