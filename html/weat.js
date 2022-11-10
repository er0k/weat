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

const getWeather = async service => {
    let url = `w.php?s=${service.id}`;
    if (service.ip) {
        url += `&ip=${service.ip}`;
    }
    if (service.nocache) {
        url += `&nocache=${service.nocache}`;
    }

    let weatherResp = await fetch(url);
    let contents = await weatherResp.json();
    return contents;
}

const showWeather = async service => {
    let data = await getWeather(service);
    window.location.hash = '#' + service.name;
    console.log(service.name, data);

    for (let item in data.location) {
        if (document.getElementById(item)) {
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

if (window.location.search) {
    let urlParams = new URLSearchParams(window.location.search);
    let entries = urlParams.entries();
    let extra = {};
    for(let entry of entries) {
        extra[`${entry[0]}`] = `${entry[1]}`;
    }
    services.map(service => {
        for(let e in extra) {
            service[`${e}`] = `${extra[e]}`;
        }
    });
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
