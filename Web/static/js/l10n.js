function tr(string, ...args) {
    let output = window.lang[string];
    if(args.length > 0) {
        if(typeof args[0] === "number") {
            const cardinal = args[0];
            let numberedString;

            switch(cardinal) {
                case 0: 
                    numberedString = string + "_zero";
                    break;
                case 1: 
                    numberedString = string + "_one";
                    break;
                default:
                    numberedString = string + (cardinal < 5 ? "_few" : "_other");
            }

            let newOutput = window.lang[numberedString];
            if(newOutput == null)
                newOutput = window.lang[string + "_other"];

            if(newOutput == null)
                newOutput = output;

            output = newOutput;
        }
    }

    if(output == null)
        return "@" + string;

    for(const [ i, element ] of Object.entries(args))
        output = output.replace(RegExp("(\\$" + (Number(i) + 1) + ")"), element);

    return output;
}
