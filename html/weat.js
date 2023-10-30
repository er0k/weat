const WEAT = "/weat.php"

const fetchUrl = async url => {
    let resp = await fetch(url);
    return resp.json();
}

const getServices = async _=> {
    return await fetchUrl(`${WEAT}?l`);
}

const getWeather = async service => {
    return await fetchUrl(`${WEAT}?s=${service.id}`);
}

function fetchData() {
    return {
        services: null,
        weather: null,
        location: null,
        sun: null,
        activeButton: null,
        activateService(service, index) {
            this.activeButton = index;
            this.getWeatherFromService(service, true);
        },
        async getWeatherFromService(service, activate = false) {
            let weather = await getWeather(service);
            this.location = weather.location;
            this.sun = weather.sun;
            if (activate) {
                this.weather = weather.weather;
            }
        },
        get _() {
            return (async _=> {
                let services = await getServices();
                this.services = services;
                for (let [i, service] of services.entries()) {
                    if (i == 0) {
                        this.activateService(service, i);
                    } else {
                        this.getWeatherFromService(service);
                    }
                }
            })();
        },
    };
}
