import { AxiosInstance } from "axios";
import { route as ziggyRoute } from "ziggy-js";

declare global {
    interface Window {
        axios: AxiosInstance;
    }

    var route: typeof ziggyRoute;

    namespace NodeJS {
        interface Global {
            route: typeof ziggyRoute;
        }
    }
}
