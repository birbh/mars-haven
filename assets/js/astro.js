setInterval(()=>{
    fetch('astronaut.php?refresh=1')
    .then((response)=> response.text())
    .then((data)=>{
        const dash=document.getElementById('dashboard-content');
        if(dash){
            dash.innerHTML=data;
        }
    })
    .catch((error)=>{
        console.log('Refresh Failed:',error);
    })
},7000);
