<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
require "DB_params.php" ?>
<script>
function enableLogin() {
    const signinnav = document.getElementById("logindiv");
        signinnav.innerHTML = `
        <div id="loginform">
            <form id="logform" method="POST">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
                <button type="submit">Sign In</button>
                <button type="button" onclick="disableLogin()">Cancel</button>
            </form>
        </div>
        `;
        document.getElementById("logform").addEventListener("submit", async function (e) {
    e.preventDefault();

    const username = document.getElementById("username").value;
    const password = document.getElementById("password").value;

    const response = await fetch("http://localhost:8080/login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ "username": username, "password":password })
    });

    const result = await response.json();

    if (response.ok && result.token) {
        localStorage.setItem("token", result.token);
        localStorage.setItem("username", username); // Store the username in localStorage
        alert("Login successful!");

        // Display the username in the header
        document.getElementById("userdiv").style.display = "block";
        document.getElementById("msg").textContent = `Welcome, ${username}!`;
        document.getElementById("opa").innerHTML = `<a href="OwnerPage.php?Owner=${username}" target="_blank">Προβολή/Επεξεργασία Παρατηρίων</a>`;        document.getElementById("libutt").innerHTML = `<button id="elbutton" onclick="logout()">Logout</button>`;
        document.getElementById("logindiv").innerHTML = ``;
        document.getElementById("showOrders").style.display = "block"; // Show the orders button
        document.getElementById("showMyOrders").style.display = "none"; // Show the orders div
        showMyOrders(username); // Call the function to show orders after login
        
    } else {
        alert("Login failed: " + (result.error || "Unknown error"));
    }
});
}
function logout() {
    localStorage.removeItem("token");
    localStorage.removeItem("username"); // Remove the username from localStorage
    document.getElementById("userdiv").style.display = "none";
    document.getElementById("msg").textContent = "";
    document.getElementById("libutt").innerHTML = `<button id="ellbutton" onclick="enableLogin()">Login</button>`;
    disableLogin();
    location.reload(); // Reload the page to reset the state
}
function disableLogin() {
        const signinnav = document.getElementById("logindiv");
        signinnav.innerHTML = ``;
}
document.addEventListener("DOMContentLoaded", function () {
    const username = localStorage.getItem("username");
    const showOrdersButton = document.getElementById("showOrders");
    const ordersDiv = document.getElementById("showMyOrders");
    if (username) {
        document.getElementById("userdiv").style.display = "block";
        document.getElementById("msg").textContent = `Welcome, ${username}!`;
        document.getElementById("opa").innerHTML = `<a href="OwnerPage.php?Owner=${username}" target="_blank">Προβολή/Επεξεργασία Παρατηρίων</a>`;        document.getElementById("libutt").innerHTML = `<button id="elbutton" onclick="logout()">Logout</button>`;

        document.getElementById("libutt").innerHTML = `<button id="elbutton" onclick="logout()">Logout</button>`;
        showOrdersButton.style.display = "block";
        ordersDiv.style.display = "none";
        showMyOrders(username); // Call the function to show orders on page load
    }else{
        document.getElementById("userdiv").style.display = "none";
        showOrdersButton.style.display = "none";
    }
});
function showMyOrders(username) {
    const token = localStorage.getItem("token"); // Retrieve the token from localStorage

    if (!username || !token) {
        alert("You must be logged in to view your orders.");
        return;
    }

    fetch(`http://localhost:8080/GetOrders?username=${encodeURIComponent(username)}&dif=true`, {
        method: "GET",
        headers: {
            "Authorization": `Bearer ${token}`
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(orders => {
        const ordersDiv = document.getElementById("showMyOrders");
        ordersDiv.innerHTML = "<h3>Your Orders:</h3>";

        if (Array.isArray(orders) && orders.length > 0) {
            orders.forEach(order => {
                let orderDetails = `
                    <div class="order">
                        <p><strong>Order ID:</strong> ${order.orderID}</p>
                        <p><strong>Gas Station:</strong> ${order.gasStationOwner}</p>
                        <p><strong>Fuel Type:</strong> ${order.fuelNormalName}</p>
                        <p><strong>Quantity:</strong> ${order.quantity}</p>
                        <p><strong>Date:</strong> ${order.when}</p>
                        <p><strong>Order Status:</strong> ${order.status}</p>
                    </div>
                `;
                if(order.status== "pending"){
                    orderDetails += `
                        <button onclick="cancelOrder(${order.orderID})">Cancel Order</button>
                    `;
                }
                orderDetails += `
                    <hr>
                `;
                ordersDiv.innerHTML += orderDetails;

            });
        } else {
            ordersDiv.innerHTML += "<p style='text-align:center; color:red; font-size:19px; margin-top:30px;'>No orders found.</p>";
        }
    })
    .catch(error => {
        console.error("Error fetching orders:", error);
        alert("An error occurred while fetching your orders. Please try again later.");
    });
}
function cancelOrder(orderID) {
    const token = localStorage.getItem("token"); // Retrieve the token from localStorage

    if (!token) {
        alert("You must be logged in to cancel an order.");
        return;
    }

    fetch(`http://localhost:8080/DeleteOrder/${orderID}`, {
        method: "DELETE",
        headers: {
            "Authorization": `Bearer ${token}`
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(result => {
        alert("Order cancelled successfully.");
        showMyOrders(localStorage.getItem("username")); // Refresh the orders list
    })
    .catch(error => {
        console.error("Error cancelling order:", error);
        alert("An error occurred while cancelling the order. Please try again later.");
    });
}
function showOrdersf() {
    const ordersDiv = document.getElementById("showMyOrders");
    const showOrdersButton = document.getElementById("showOrders");

    if (ordersDiv.style.display === "none" || ordersDiv.style.display === "") {
        ordersDiv.style.display = "block";
        showOrdersButton.innerHTML = "Hide Orders";
        showMyOrders(localStorage.getItem("username")); // Fetch and display orders
    } else {
        ordersDiv.style.display = "none";
        showOrdersButton.innerHTML = "Show Orders";
    }
}
</script>
<?php 
require "includemapjs.php";
$header = "Gas Stations Map";
$header2 = "You can now see the available Gas Stations on the map with all their details and available fuel prices";
require "header.php" ?>
<main id="mainmap">
    <div id="try">
    </div>
    <div id="logindiv"></div>
    <div id="markerdetails" style="display: none;"></div>
    <div id="showMyOrders">
    </div>
    <?php require "nav.php" ?>
    <div id="map" style="width: 100%; height: 100%;"></div>
</main>
<?php require "footer.php" ?>