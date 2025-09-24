<script
  src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDzSdchdk1Zr4eIYGquKhg_rRiKUd8LhZA&callback=initMap"
  async defer>
</script>

<script>
  function hideMarkerDetails(){
    const markerDetailsDiv = document.getElementById("markerdetails");
    markerDetailsDiv.style.display = "none"; 
    markerDetailsDiv.innerHTML = ""; 
  }
  let map; 
  let markers = [];
  populateDropdownWithFuels();
  function doOrder(productID,liters,price) {
    const token = localStorage.getItem("token");
    const username = localStorage.getItem("username");
    
    if (!token || !username) {
        alert("Please log in to place an order.");
        return;
    }

    if (!productID) {
        alert("Invalid product ID.");
        return;
    }

    const url = "http://localhost:8080/Order";


    fetch(url, {
        method: "POST",
        headers: {
            "Authorization": `Bearer ${token}`,
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            productID: productID,
            username: username,
            liters: liters
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log("Order Response:", data);
        if (data.success) {
            alert("Order placed successfully for "+liters+" liters at "+(liters*price)+"€ !");
        } else {
            alert("Failed to place order: " + (data.message || "Unknown error"));
        }
    })
    .catch(error => {
        console.error("Error placing order:", error);
        alert("An error occurred while placing the order. Please try again later.");
    });
}

  function populateDropdownWithFuels() {
    fetch('http://localhost:8080/AvailableFuels')
    .then(response => response.json())
    .then(data => {
      console.log("Available Fuels Data:", data);

      if (!Array.isArray(data)) {
        throw new Error("Data is not an array");
      }

      const dropdownList = document.querySelector("#menu > ul > li > ul.dropdownlist");
      dropdownList.innerHTML = `<li><label><input type="radio" name="fuelType" value="" checked ><span>'Oλα τα καυσαέρια</span></label></li>`; // Add "All Fuels" option

      data.forEach(fuel => {
        const listItem = document.createElement("li");
        listItem.innerHTML = `<label><input type="radio" name="fuelType" value="${fuel.fuelTypeID}"><span>${fuel.fuelNormalName}</span></label>`;
        dropdownList.appendChild(listItem);
      });

      addEventListenersToFuelRadios();
    })
    .catch(error => {
      console.error("Error fetching available fuels:", error);
    }); 
  }
  function addEventListenersToFuelRadios() {
    const radios = document.querySelectorAll('input[name="fuelType"]');
    radios.forEach(radio => {
      radio.addEventListener("change", (event) => {
        const fuelTypeID = event.target.value;
        if(fuelTypeID.trim() === "") {
          initMap(null);
        }else{
            console.log("Selected Fuel Type ID:", fuelTypeID);
          
            initMap(fuelTypeID);
        }
      });
    });
  }

  function clearMarkers() {
    
    markers.forEach(marker => marker.setMap(null));
    markers = [];
  }

  function fetchGasStationsByFuels(fuelTypeID) {
    const apiUrl = `http://localhost:8080/GasStationsByFuels/${fuelTypeID}`;
    fetch(apiUrl)
      .then(response => response.json())
      .then(data => {
        console.log("Gas Stations Data:", data);
        if (!Array.isArray(data)) {
          throw new Error("Data is not an array");
        }
        clearMarkers();

        
        data.forEach(station => {
          const marker = new google.maps.Marker({
            position: { lat: parseFloat(station.gasStationLat), lng: parseFloat(station.gasStationLong) },
            map: map,
            title: station.gasStationOwner,
          });
          marker.addListener("click", () => {
            fetchGasStationPrices(station.gasStationID);
          });

          markers.push(marker);
        });

        
        const tryDetailsDiv = document.getElementById("trydetails");
        tryDetailsDiv.innerHTML = "";
        data.forEach(station => {
          const stationDetails = `
            <p>
              <strong>Gas Station:</strong> ${station.gasStationOwner}<br>
              <strong>Location:</strong> (${station.gasStationLat}, ${station.gasStationLong})
            </p>`;
          tryDetailsDiv.innerHTML += stationDetails;
        });
      })
      .catch(error => {
        console.error("Error fetching gas stations:", error);
        const tryDetailsDiv = document.getElementById("trydetails");
        tryDetailsDiv.innerHTML = "<p>Error fetching gas stations. Please try again later.</p>";
      });
  }

  function fetchGasStationPrices(gasStationID) {
    fetch(`http://localhost:8080/GasStationPrices/${gasStationID}`)
      .then(response => response.json())
      .then(data => {
        console.log("Gas Station Prices Data:", data);
        if (!Array.isArray(data)) {
          throw new Error("Data is not an array");
        }
        const tryDetailsDiv = document.getElementById("markerdetails");
        tryDetailsDiv.innerHTML = "";
        tryDetailsDiv.style.display = "block"; 
        const backbutton = document.createElement("button");
        backbutton.id = "backbutton";
        backbutton.position = "absolute";
            backbutton.innerHTML = "&#128281; Back";
            backbutton.style.display = "block";
            backbutton.innerHTML = "&#128281;";
                backbutton.onclick = function () {
                console.log("Back button clicked");
                hideMarkerDetails(); 
              };
        const gasStationOwner = data[0].gasStationOwner; 
        const fuelCompNormalName = data[0].fuelCompNormalName; 
        const gasStationAddress = data[0].gasStationAddress;  
        const phone1 = data[0].phone1; 
        const phone2 = data[0].phone1;
        tryDetailsDiv.innerHTML = `
          <p style="font-size:30px;">Πρατήριο: ${gasStationOwner} - ${fuelCompNormalName}</p>
          <p>Διεύθυνση: ${gasStationAddress}</p>
        `;
        if(phone1){
          tryDetailsDiv.innerHTML += `<p>Phone: ${phone1}</p>`;
        }else{
          const msg = "Δεν υπάρχει τηλέφωνο";
          tryDetailsDiv.innerHTML += `<p>${msg}</p>`;
        }
        tryDetailsDiv.appendChild(backbutton); 
        data.forEach(price => {
          const priceDetails = document.createElement("div");
          priceDetails.className = "price-details"; 
                priceDetails.style.marginTop = "50px";
                priceDetails.innerHTML = `
                    <p>Τύπος Καυσίμου: ${price.fuelName} <br> Τιμή: ${price.fuelPrice}</p>
                `;

                const orderButton = document.createElement("button");
                const liters = document.createElement("input");
                liters.style.width= "50%";
                liters.style.marginRight = "10px";
                liters.type = "number";
                liters.placeholder = "Ποσότητα σε λίτρα";
                liters.min = "1"; 
                liters.max = "100"; 
                priceDetails.appendChild(liters); 

                orderButton.textContent = "Παραγγελία"; 
                orderButton.onclick = function () {
                    console.log("Order button clicked for productID:", price.productID); 
                    doOrder(price.productID,liters.value,price.fuelPrice); 
                };

                priceDetails.appendChild(orderButton);

                tryDetailsDiv.appendChild(priceDetails);
        });
      })
      .catch(error => {
        console.error("Error fetching gas station prices:", error);
      });
  }

  function initMap(fuelTypeID = null) {
    const center = { lat: 38.0, lng: 23.7 }; 
    if (!map) {
      map = new google.maps.Map(document.getElementById("map"), {
        zoom: 7 ,
        center: center,
      });
    }

    const apiUrl = fuelTypeID
      ? `http://localhost:8080/GasStationsByFuels/${fuelTypeID}`
      : 'http://localhost:8080/GasStations';

    fetch(apiUrl)
      .then(response => response.json())
      .then(data => {
        console.log("Gas Stations Data:", data);
        if (!Array.isArray(data)) {
          throw new Error("Data is not an array");
        }

        clearMarkers();

        data.forEach(station => {
          const logoUrl="logos/"+station.fuelCompID+".png";
          const marker = new google.maps.Marker({
            position: { lat: parseFloat(station.gasStationLat), lng: parseFloat(station.gasStationLong) },
            map: map,
            title: station.gasStationOwner,
            icon:{
              url:logoUrl,
              scaledSize: new google.maps.Size(40, 40),
              anchor: new google.maps.Point(20, 20) 
            }
          });

          marker.addListener("click", () => {
            fetchGasStationPrices(station.gasStationID);
          });

          markers.push(marker);
        });
      })
      .catch(error => {
        console.error("Error fetching gas stations:", error);
      });

    const api = fuelTypeID 
  ? `http://localhost:8080/AvgMinMaxAvg/${fuelTypeID}` 
  : "http://localhost:8080/AvgMinMaxAvg";
    fetch(api)
        .then(response => response.json())
        .then(data => {
          console.log("Aggregated Data:", data);

          const tryDetailsDiv = document.getElementById("try");
          tryDetailsDiv.innerHTML = `<p>Gas Station Count: ${data.station_count}</p><p> Avarege fuel Price: ${data.avg_price}, </br> Maximum Fuel Price: ${data.max_price}&nbsp;&nbsp;&nbsp; Minimum Fuel Price: ${data.min_price}</p>`;
        })
        .catch(error => {
          console.error("Error fetching aggregated data:", error);
        });
}
</script>   