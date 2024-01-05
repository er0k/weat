const WEAT = "/weat.php"

const fetchUrl = async url => {
    let resp = await fetch(url);
    return resp.json();
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
            let weather = await fetchUrl(`${WEAT}?w=${service.id}`);
            if (activate) {
                this.weather = weather;
            }
        },
        get _() {
            return (async _=> {
                this.sun = await fetchUrl(`${WEAT}?s`);
                this.moon = await fetchUrl(`${WEAT}?m`);
                this.location = await fetchUrl(`${WEAT}?x`);

                let services = await fetchUrl(`${WEAT}?l`);
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
