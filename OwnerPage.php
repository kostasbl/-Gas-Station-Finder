<?php require "DB_params.php" ?>
<script>
    const $user = localStorage.getItem("username");
    if (localStorage.getItem("token")&&$user) {
        console.log("User is logged in:", $user);
        loadMyStations($user);
        fetch(`http://localhost:8080/GetOrders?username=${encodeURIComponent($user)}`, {
            method: "GET",
            headers: {
                "Authorization": `Bearer ${localStorage.getItem("token")}`
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log("Orders Data:", data);
            const ordersDiv = document.getElementById("orders");
            if (Array.isArray(data) && data.length > 0) {
                ordersDiv.innerHTML = "<h6>Your Orders:</h6>";
                data.forEach(order => {
                    const orderDetails = `
                        <div class="order">
                            <p><strong>Gas Station:</strong> ${order.gasStationOwner}- ${order.fuelCompNormalName}</p>
                            <p><strong>Address:</strong> ${order.gasStationAddress}</p>
                            <p><strong>Fuel Type:</strong> ${order.fuelName}</p>
                            <p><strong>Order ID:</strong> ${order.orderID}</p>
                            <p><strong>Product ID:</strong> ${order.productID}</p>
                            <p><strong>From user:</strong> ${order.customerUsername}</p>
                            <p><strong>Quantity:</strong> ${order.quantity}</p>
                            <p><strong>Date:</strong> ${order.when}</p>
                            <button onclick="acceptorder(${order.orderID})">Accept order</button>
                            <button onclick="declineorder(${order.orderID})">Decline order</button>
                        </div>
                        <hr>
                    `;
                    ordersDiv.innerHTML += orderDetails;
                });
            } else {
                ordersDiv.innerHTML = "<p style='text-align:center; color:red; font-size:19px; margin-top:30px;'>No orders found.</p>"
                +"<p style='text-align:center;'>(Looks like you dont have orders!)</p>";
            }
        })
        .catch(error => {
            console.error("Error fetching orders:", error);
            document.getElementById("orders").innerHTML = "<p>Error fetching orders. Please try again later.</p>";
        });
    } else {
        window.location.href = "index.php"; // Redirect to index if not logged in
    }
    function acceptorder(orderID) {
        fetch('http://localhost:8080/UpdateOrderStatus', {
        method: 'PUT',
        headers: {
            'Authorization': `Bearer ${localStorage.getItem("token")}`,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            orderID: orderID,
            status: 'accepted'
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log("Order Accepted Response:", data);
        if (data.success) {
            alert("Order accepted successfully!");
            location.reload(); 
        } else {
            alert("Failed to accept order: " + (data.message || "Unknown error"));
        }
    })
    .catch(error => {
        console.error("Error accepting order:", error);
        alert("An error occurred while accepting the order. Please try again later.");
    });
    }
    function declineorder(orderID) {
        fetch(`http://localhost:8080/DeleteOrder/${orderID}`, {
        method: "DELETE",
        headers: {
            "Authorization": `Bearer ${localStorage.getItem("token")}`
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log("Order Decline Response:", data);
        if (data.success) {
            alert("The order was successfully declined.");
            location.reload();
        } else {
            alert("Failed to decline the order: " + (data.message || "Unknown error."));
        }
    })
    .catch(error => {
        console.error("Error declining order:", error);
        alert("An error occurred while declining the order. Please try again later.");
    });
    }
    function loadMyStations(userr) {

    fetch(`http://localhost:8080/GetGasStations?username=${encodeURIComponent(userr)}`, {
        method: "GET",
        headers: {
            "Authorization": `Bearer ${localStorage.getItem("token")}`
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log("Gas Stations Data:", data);
        const stationsDiv = document.getElementById("mystations");
        const stationPricesDiv = document.getElementById("stationprices");
        if (Array.isArray(data) && data.length > 0) {
            stationsDiv.innerHTML = "<h4>Your Gas Station(s):</h4>";
            data.forEach(station => {
                const stationDetails = `
                    <div class="station">
                        <p><strong>Station ID:</strong> ${station.gasStationID}</p>
                        <p><strong>Owner:</strong> ${station.gasStationOwner}</p>
                        <p><strong>Address:</strong> ${station.gasStationAddress}</p>
                        <p><strong>Fuel Company:</strong> ${station.fuelCompNormalName}</p>
                    </div>
                    <hr>
                `;
                stationsDiv.innerHTML += stationDetails;
                fetch(`http://localhost:8080/GasStationPrices/${station.gasStationID}`)
                .then(priceResponse => {
                    if (!priceResponse.ok) {
                        throw new Error(`HTTP error! Status: ${priceResponse.status}`);
                    }
                    return priceResponse.json();
                })
                .then(prices => {
                    if (Array.isArray(prices) && prices.length > 0) {
                        stationPricesDiv.innerHTML += `<h5>Fuel Prices for Station ID ${station.gasStationID}:</h5>`;
                        let priceList = "<form><ol>";
                        prices.forEach(price => {
                            priceList += `<li>${price.fuelNormalName}: <input name="${price.productID}" value="${price.fuelPrice}"/></li>`;
                        });
                        priceList += `</ol><button id="b-${station.gasStationID}" type="button">Confirm Change</button></form>`;
                        stationPricesDiv.innerHTML += priceList;

                        document.getElementById(`b-${station.gasStationID}`).addEventListener("click", function () {
                            const inputs = stationPricesDiv.querySelectorAll(`input`);
                            const updates = [];
                            inputs.forEach(input => {
                                const productID = input.name;
                                const newValue = input.value;
                                console.log(`Updating price for Product ID: ${productID}, New Value: ${newValue}`);
                                updates.push(updatefuelPrice(station.gasStationID, productID, newValue));
                            });
                            Promise.all(updates)
                            .then(() => {
                                alert("All prices updated successfully!");
                            })
                            .catch(error => {
                                console.error("Error updating prices:", error);
                                alert("An error occurred while updating prices. Please try again.");
                            });
                        });
                    } else {
                        stationPricesDiv.innerHTML += `<p>No prices available for Station ID ${station.gasStationID}.</p>`;
                    }
                })
                .catch(priceError => {
                    console.error("Error fetching prices:", priceError);
                    stationPricesDiv.innerHTML += `<p>Error loading prices for Station ID ${station.gasStationID}.</p>`;
                });
            });
        } else {
            stationprices.innerHTML = "<p>No gas stations found.</p>";
        }
    })
    .catch(error => {
        console.error("Error fetching gas stations:", error);
        document.getElementById("mystations").innerHTML = "<p>Error fetching gas stations. Please try again later.</p>";
    });
    function updatefuelPrice(stationID,productID,newValue){
    let success = false;
    fetch(`http://localhost:8080/UpdateFuelPrice`, {
        method: "PUT",
        headers: {
            "Authorization": `Bearer ${localStorage.getItem("token")}`,
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            stationID: stationID,
            productID: productID,
            newPrice: newValue
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log("Price updated successfully:", data);
    })
    .catch(error => {
        console.error("Error updating price:", error);
    });
}
}
</script>
<?php 
$header = "Owner page";
$header2 = "In this page you can view and edit your <br>gas stations fuel prices and manage your orders.";
require "header.php" ?>
<main id="mainop">
    <div id="divmainop">
        <h3>Welcome to the Owner Page</h3>
    </div>
    <div id="mystations">
    </div>
    <div id="stationprices"></div>
    <div id="orders">
    </div>

</main> 
<?php require "footer.php" ?>
