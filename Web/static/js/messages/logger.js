export function isImVerboseLogging() {
    try {
        return localStorage.getItem("im.verbose_logging") === "1"
            || localStorage.getItem("im.verbose_logging") === "true"
            || localStorage.getItem("tw.im.verbose_logging") === "1";
    } catch (e) {
        return false;
    }
}

export function imLog(...args) {
    if (isImVerboseLogging()) {
        console.log("[IM]", ...args);
    }
}

imLog.info = function (...args) {
    if (isImVerboseLogging()) {
        console.info("[IM]", ...args);
    }
};

imLog.warn = function (...args) {
    if (isImVerboseLogging()) {
        console.warn("[IM]", ...args);
    }
};

imLog.error = function (...args) {
    if (isImVerboseLogging()) {
        console.error("[IM]", ...args);
    }
};

imLog.debug = function (...args) {
    if (isImVerboseLogging()) {
        console.debug("[IM]", ...args);
    }
};

imLog.table = function (...args) {
    if (isImVerboseLogging()) {
        console.table(...args);
    }
};

imLog.group = function (...args) {
    if (isImVerboseLogging()) {
        console.group("[IM]", ...args);
    }
};

imLog.groupEnd = function () {
    if (isImVerboseLogging()) {
        console.groupEnd();
    }
};

imLog.enable = function () {
    try {
        localStorage.setItem("im.verbose_logging", "1");
        localStorage.setItem("tw.im.verbose_logging", "1");
    } catch (e) { }
    console.log("%c[IM] Verbose logging ENABLED (im.verbose_logging = 1)", "color: #4bb34b; font-weight: bold;");
};

imLog.disable = function () {
    try {
        localStorage.removeItem("im.verbose_logging");
        localStorage.removeItem("tw.im.verbose_logging");
    } catch (e) { }
    console.log("%c[IM] Verbose logging DISABLED", "color: #e64646; font-weight: bold;");
};

imLog.isEnabled = isImVerboseLogging;

if (typeof window !== "undefined") {
    window.imLog = imLog;
    window.isImVerboseLogging = isImVerboseLogging;
}
