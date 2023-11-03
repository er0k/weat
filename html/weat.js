const WEAT = "/weat.php"

const fetchUrl = async url => {
    let resp = await fetch(url);
    return resp.json();
}

const getWeatherServices = async _=> {
    return await fetchUrl(`${WEAT}?l`);
}

const getLocation = async _=> {
    return await fetchUrl(`${WEAT}?x`);
}

const getSun = async _=> {
    return await fetchUrl(`${WEAT}?s`);
}

const getMoon = async _=> {
    return await fetchUrl(`${WEAT}?m`);
}

const getWeather = async service => {
    return await fetchUrl(`${WEAT}?w=${service.id}`);
}


function fetchData() {
    return {
        services: null,
        weather: null,
        location: null,
        moon: null,
        sun: null,
        activeButton: null,
        activateService(service, index) {
            this.activeButton = index;
            this.getWeatherFromService(service, true);
        },
        async getWeatherFromService(service, activate = false) {
            let weather = await getWeather(service);
            if (activate) {
                this.weather = weather;
            }
        },
        get _() {
            return (async _=> {

                this.sun = await getSun();
                this.moon = await getMoon();
                this.location = await getLocation();

                let services = await getWeatherServices();
                this.services = services;
                for (let [i, service] of services.entries()) {
                    console.log(i, service);
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
