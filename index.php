<?php require "DB_params.php" ?>
<script>
    function enableSignIn() {
        const signinnav = document.getElementById("signinnav");
        signinnav.innerHTML = `
        <div id="signinform">
            <form action="signin.php" method="POST">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" size="20" required>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" size="20" required>
                <label for="email">Email:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                <input type="text" id="email" name="email" size="20" required>
                <select id="user" name="user" style="width: 100%; height: 30px; margin-top: 10px; margin-bottom: 10px;">
                    <option value="" disabled selected>Select User Type</option>
                    <option value="consumer">Consumer</option>
                    <option value="owner">Owner</option>
                </select>
                <button type="submit">Sign In</button>
                <button type="button" onclick="disableSignIn()">Cancel</button>
            </form>
        </div>
        `;
    }
    function disableSignIn() {
        const signinnav = document.getElementById("signinnav");
        signinnav.innerHTML = ``;
    }
</script>
<?php 
$header = "Welcome to Fuels";
$header2 = "Welcome to FuelsGR";
require "header.php" ?>
<main id="main">
    <div id="mainimgdiv">
    <nav id="signinnav">
    </nav>
    <img src="images/mainimg.jpg" alt="World Image" >
    
    <div id="imgtext">
    <h3>Welcome to our Fuel website</h3>
    <p>In this site we generate all the information about your Gas Stations</p>
    <p>IF you are intrested on doing a reservation create yourself a consumer Account</p>
    <p>Otherwise if you are intrested on adding your own Gas Station in the map ... Create yourself an Owner Account !!</p>
    <p>You can check below the available Gas Stations on the map </p>
    <button >
        <a href="map.php" target="_blank">Check the Map</a>
    </button>
    <button id="signinbutton" onclick="enableSignIn()"> Create Account</button>
    </div>
</div>
</main>
<?php require "footer.php" ?>