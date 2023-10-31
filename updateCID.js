const nsToken = "nss_HdB9NssBCflhJAhYdqGB72Gt5rE13eYfC0IQY9XvQe986iJF8d597f3b"


const numberDropdown = document.querySelector("#numberDropdown");
const message = document.querySelector("#message");

numberDropdown.addEventListener("click", function(e){
    e.preventDefault();

    console.log("Button Clicked!");
});




const updateNumber = async function (user, name, number) {
    console.log("Update pending..." + `\n${user}, ${name}, ${number}`);
    let response = await fetch("https://crexendo-core-031-mci.crexendo.ucaas.run/ns-api/?object=subscriber&action=update", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Authorization": `Bearer nss_HdB9NssBCflhJAhYdqGB72Gt5rE13eYfC0IQY9XvQe986iJF8d597f3b`
        },

        body: JSON.stringify({
            "uid": `${user}`,
            "callid_name": `${name}`,
            "callid_nmbr": `${number}`
        })
    })
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