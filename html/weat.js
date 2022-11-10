const services = [
    { id: 2, name: "OpenWeatherMap" },
    { id: 3, name: "NOAA" },
];

const showServices = _=> {
    let list = "<ul>";
    services.map(service => {
        let stringifiedService = JSON.stringify(service)
        list += `<li><a href="#${service.name}" onclick=showWeather(${stringifiedService})>${service.name}</a></li>`
    })
    list += "</ul>";
    let servicesDiv = document.getElementById("services")
    servicesDiv.innerHTML = list;
}

const getWeather = async serviceId => {
    let weatherResp = await fetch("w.php?s=" + serviceId);
    let contents = await weatherResp.json();
    return contents;
}

const showWeather = async service => {
    let data = await getWeather(service.id);
    window.location.hash = '#' + service.name;
    console.log(service, data);

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

    for (let item in data.sun) {
        if (document.getElementById(item)) {
            document.getElementById(item).textContent = data.sun[item].date;
        }
    }
}

if (window.location.hash) {
    let presetService = services.find(service => service.name === decodeURI(window.location.hash).substr(1));
    showWeather(presetService);
} else {
    services.forEach(service => {
        showWeather(service);
    })
}

showServices();
