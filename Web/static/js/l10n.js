function tr(string, ...arg) {
    let output = window.lang[string];
    if(arg.length > 0) {
        if(typeof arg[0] == 'number') {
            let numberedStringId;
            let cardinal = arg[0];
            switch(cardinal) {
                case 0: 
                    numberedString = string + '_zero';
                    break;
                case 1: 
                    numberedString = string + '_one';
                    break;
                default:
                    numberedString = string + (cardinal < 5 ? '_few' : '_other');
            }

            let newoutput = window.lang[numberedString];
            if(newoutput === null) {
                newoutput = window.lang[string + '_other'];
                if(newoutput === null) {
                    newoutput = output;
                }
            }

            output = newoutput;
        }
    }
    
    let i = 1;
    arg.forEach(element => {
        output = output.replace(RegExp('(\\$' + i + ')'), element);
        i++;
    });
    return output;
}