const services = [
    { id: 2, name: "OpenWeatherMap" },
    { id: 3, name: "NOAA" },
];

const showServices = _=> {
    let list = "<ul>";
    services.map(service => list += `<li><a href="#${service.name}" onclick="showWeather(${service.id});">${service.name}</a></li>`);
    list += "</ul>";
    let servicesDiv = document.getElementById("services")
    servicesDiv.innerHTML = list;
}

showServices();

const getWeather = async serviceId => {
    let weatherResp = await fetch("w.php?s=" + serviceId);
    let contents = await weatherResp.json();
    return contents;
}

const showWeather = async serviceId => {
    let data = await getWeather(serviceId);
    console.log(serviceId, data);

    for (let item in data.location) {

        if (document.getElementById(item)) {
            console.log(item, data.location[item]);
            document.getElementById(item).textContent = data.location[item];
        }
    }

    for (let item in data.weather) {
        if (document.getElementById(item)) {
            if (item == "currentIcon") {
                document.getElementById(item).src = data.weather[item];
            } else {
                document.getElementById(item).textContent = data.weather[item];
            }
        }
    }
}

if (window.location.hash) {
    let presetService = services.find(service => service.name === decodeURI(window.location.hash).substr(1));
    showWeather(presetService.id);
}
