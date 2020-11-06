try {
    window._ = require("lodash");

    require("bootstrap");
} catch (e) {}

/**
 * Set CSRF token as a header based on the value of the "XSRF" token cookie.
 */
window.axios = require("axios");
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

require("./components/Greet");
