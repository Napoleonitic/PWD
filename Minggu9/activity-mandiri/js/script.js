function validateForm() {
    var name = document.forms["myForm"]["name"].value;
    var email = document.forms["myForm"]["email"].value;
    var age = document.forms["myForm"]["age"].value;
    if (name == "" || email == "" || age == "") {
        alert("Isi Semua Field Dulu Brog");
        return false;
    }
    var regex = /^[a-z0-9][a-z0-9_\.-]{0,}[a-z0-9]@[a-z0-9][a-z0-9_\.-]{0,}[a-z0-9]\.[a-z0-9]{2,4}$/;
    if (!regex.test(email)) {
        alert("Format Email Tidak Masuk Akal");
        return false;
    }
    if (isNaN(age) || age <= 0) {
        alert("Masukkan umur yang logis");
        return false;
    }
    alert("Data Sent! :D");
    return true;
}
