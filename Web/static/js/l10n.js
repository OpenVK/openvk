function tr(string, ...args) {
    let output = window.lang[string];
    if (args.length > 0 && typeof args[0] === "number") {
        const n = Math.abs(args[0]);
        
        let numberedString;
        
        if (n === 0) {
            numberedString = string + "_zero";
        } else {
            let temp = n % 100;
            if (temp >= 5 && temp <= 20) {
                numberedString = string + "_other";
            } else {
                temp = n % 10;
                if (temp === 1) {
                    numberedString = string + "_one";
                } else if (temp >= 2 && temp <= 4) {
                    numberedString = string + "_few";
                } else {
                    numberedString = string + "_other";
                }
            }
        }

        let newOutput = window.lang[numberedString];
        if(newOutput == null)
            newOutput = window.lang[string + "_other"];

        if(newOutput == null)
            newOutput = output;

        output = newOutput;
    }

    if(output == null)
        return "@" + string;

    for(const [ i, element ] of Object.entries(args))
        output = output.replace(RegExp("(\\$" + (Number(i) + 1) + ")"), element);

    return output;
}
