function chooseHomeTemplate(user){

    if(!user.logged){
        return "base";
    }

    if(user.role === "studente"){

        if(user.level >= 2){
            return "studentRep";
        }

        return "student";
    }

    if(user.role === "professore"){
        return "professor";
    }

    if(user.role === "segreteria"){
        return "segretary";
    }

    return "base";
}