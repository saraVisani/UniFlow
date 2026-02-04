function chooseHomeTemplate(user){

    if(!user.logged){
        return "base";
    }

    if(user.level === 1){
        return "student";
    }else if(user.level === 2){
        return "studentRep";
    }else if(user.level === 3){
        return "professor";
    }else{
        return "segretary";
    }
}

const UPLOAD_DIR = '../Img/';