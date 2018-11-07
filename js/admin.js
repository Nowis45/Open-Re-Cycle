var oTable;

$(document).ready(function(){
   $("#edituser").hide();
   $("#where").click(function() { if (window.ga) ga('send', 'event', 'buttons', 'click', 'admin-where'); where(); });
   $("#removenote").click(function() { if (window.ga) ga('send', 'event', 'buttons', 'click', 'admin-removenote'); removenote(); });
   $("#removesignalement").click(function() { if (window.ga) ga('send', 'event', 'buttons', 'click', 'admin-removesignalement'); removesignalement(); });
   $("#revert").click(function() { if (window.ga) ga('send', 'event', 'buttons', 'click', 'admin-revert'); revert(); });
   $("#last").click(function() { if (window.ga) ga('send', 'event', 'buttons', 'click', 'admin-last'); last(); });
   $("#stands").click(function() { if (window.ga) ga('send', 'event', 'buttons', 'click', 'admin-stands'); stands(); });
   $("#userlist").click(function() { if (window.ga) ga('send', 'event', 'buttons', 'click', 'admin-userlist'); userlist(); });
   $("#touristeslist").click(function() { if (window.ga) ga('send', 'event', 'buttons', 'click', 'admin-touristeslist'); touristeslist(); });
   $("#userstats").click(function() { if (window.ga) ga('send', 'event', 'buttons', 'click', 'admin-userstats'); userstats(); });
   $("#usagestats").click(function() { if (window.ga) ga('send', 'event', 'buttons', 'click', 'admin-usagestats'); usagestats(); });
   $("#monthstats").click(function() { if (window.ga) ga('send', 'event', 'buttons', 'click', 'admin-monthstats'); monthstats(); });
   $("#standstats").click(function() { if (window.ga) ga('send', 'event', 'buttons', 'click', 'admin-standstats'); standstats(); });
   $("#listcoupons").click(function() { if (window.ga) ga('send', 'event', 'buttons', 'click', 'admin-couponlist'); couponlist(); });
   $("#generatecoupons1").click(function() { if (window.ga) ga('send', 'event', 'buttons', 'click', 'admin-generatecoupons'); generatecoupons(1); });
   $("#generatecoupons2").click(function() { if (window.ga) ga('send', 'event', 'buttons', 'click', 'admin-generatecoupons'); generatecoupons(5); });
   $("#generatecoupons3").click(function() { if (window.ga) ga('send', 'event', 'buttons', 'click', 'admin-generatecoupons'); generatecoupons(10); });
   $("#trips").click(function() { if (window.ga) ga('send', 'event', 'buttons', 'click', 'admin-trips'); trips(); });
   $('.nav-tabs a').each(function () { $(this).click(function () { activetab=$(this).attr('href'); $(activetab).addClass('active'); } ); });
   $("#saveuser").click(function() { saveuser(); return false; });
   $("#addcredit").click(function() { addcredit(1); return false; });
   $("#addcredit2").click(function() { addcredit(5); return false; });
   $("#addcredit3").click(function() { addcredit(10); return false; });
   last();
});

function handleresponse(elementid,jsonobject,display)
{
   if (display==undefined)
      {
      if (jsonobject.error==1)
         {
         $('#'+elementid).html('<div class="alert alert-danger" role="alert">'+jsonobject.content+'</div>').fadeIn();
         }
      else
         {
         $('#'+elementid).html('<div class="alert alert-success" role="alert">'+jsonobject.content+'</div>');
         }
      }
}

function where()
{
   if (window.ga) ga('send', 'event', 'bikes', 'where', $('#adminparam').val());
   $.ajax({
   url: "command.php?action=where&bikeno="+$('#adminparam').val()
   }).done(function(jsonresponse) {
      jsonobject=$.parseJSON(jsonresponse);
      handleresponse("fleetconsole",jsonobject);
   });
}

function removenote()
{
   if (window.ga) ga('send', 'event', 'bikes', 'removenote', $('#adminparam').val());
   $.ajax({
   url: "command.php?action=removenote&bikeno="+$('#adminparam').val()
   }).done(function(jsonresponse) {
      jsonobject=$.parseJSON(jsonresponse);
      handleresponse("fleetconsole",jsonobject);
   });
}

function last()
{
   if (window.ga) ga('send', 'event', 'bikes', 'last', $('#adminparam').val());
   $.ajax({
   url: "command.php?action=last&bikeno="+$('#adminparam').val()
   }).done(function(jsonresponse) {
      jsonobject=$.parseJSON(jsonresponse);
      handleresponse("fleetconsole",jsonobject);
   });
}

function stands()
{
   $.ajax({
   url: "command.php?action=stands"
   }).done(function(jsonresponse) {
      jsonobject=$.parseJSON(jsonresponse);
      if (jsonobject.length>0) code='<table class="table table-striped" id="userstatstable"><thead><tr><th>Station</th><th>Localisation</th><th>Nombre de vélos</th><th>Liste des vélos présents</th><th>Signalements</th></tr></thead>';
      for (var i=0, len=jsonobject.length; i < len; i++)
         {
         code=code+'<tr><td><p data-userid="'+jsonobject[i]["standid"]+'">'+jsonobject[i]["standname"]+'</p></td><td>'+jsonobject[i]["placename"]+'</td><td>'+jsonobject[i]["compte"]+'</td><td>'+jsonobject[i]["liste"]+'</td><td>'+jsonobject[i]["note"]+'<a href="#" class="removesignalement" data-standId="'+jsonobject[i]["standid"]+'"><button title="Supprimer le signalement correspondant" style="padding: 4px; margin: 10px; color: #000;">Supprimer <span class="glyphicon glyphicon-remove"></span></button></a></td></tr>';
         }
      if (jsonobject.length>0) code=code+'</table>';
      $('#standsconsole').html(code);
	  $('.removesignalement').each(function () {
		  $(this).click(function () { if (window.ga) ga('send', 'event', 'buttons', 'click', 'admin-removesignalement', $(this).attr('data-standId')); removesignalement($(this).attr('data-standId')); });
	   });
	  
   });
}

function userlist()
{
   var code="";
   $.ajax({
   url: "command.php?action=userlist"
   }).done(function(jsonresponse) {
      jsonobject=$.parseJSON(jsonresponse);
      if (jsonobject.length>0) code='<table class="table table-striped" id="usertable"><thead><tr><th>'+'n°'+'</th><th>'+_user+'</th><th>'+_penality+'</th><th>'+_limit+'</th><th>Piece d\'identité</th><th>Modifier les infos</th><th>Supprimer un utilisateur</th>';
      if (creditenabled==1) code=code+'<th>'+_credit+'</th>';
      code=code+'</tr></thead>';
      for (var i=0, len=jsonobject.length; i < len; i++)
         {
         var y = i+1;
         code=code+'<tr><td>'+y+'</td><td>'+jsonobject[i]["username"]+'<br />'+jsonobject[i]["number"]+'<br />'+jsonobject[i]["mail"]+'</td><td>0</td><td>'+jsonobject[i]["limit"]+'</td><td><a href="'+jsonobject[i]["photo"]+'" target="_blank" >Voir pièce d\'identité</a></td><td><a href="#" class="edituser" data-userid="'+jsonobject[i]["userid"]+'">Modifier</a></td><td><a href="#" class="removeuser" data-userid="'+jsonobject[i]["userid"]+'">Supprimer</a></td>';
         if (creditenabled==1)
            {
            code=code+'<td>'+jsonobject[i]["credit"]+creditcurrency+'</td></tr>';
            }
         }
      if (jsonobject.length>0) code=code+'</table>';
      $('#userconsole').html(code);
      createeditlinks();
	  
      oTable=$('#usertable').dataTable({
        "dom": 'f<"filtertoolbar">prti',
        "paging":   false,
        "ordering": false,
        "info":     false
      });
	  $('div.filtertoolbar').css("max-width", "50%");
	  $('div.filtertoolbar').css("display", "inline-block");
      $('div.filtertoolbar').html('<select id="columnfilter"><option></option></select>');
      $('#usertable th').each(function() { $('#columnfilter').append($("<option></option>").attr('value',$(this).text()).text($(this).text())); } );
	  $('#usertable_filter').css("max-width", "50%");
      $('#usertable_filter input').keyup(function() { x=$('#columnfilter').prop("selectedIndex")-1; if (x==-1) fnResetAllFilters(); else oTable.fnFilter( $(this).val(), x ); });
      $('#columnfilter').change(function() { x=$('#columnfilter').prop("selectedIndex")-1; if (x==-1) fnResetAllFilters(); else oTable.fnFilter( $('#usertable_filter input').val(), x ); });
      $('.removeuser').each(function () {
        $(this).click(function () { if (window.ga) ga('send', 'event', 'buttons', 'click', 'admin-removeuser', $(this).attr('data-userid')); removeuser($(this).attr('data-userid')); });
      });
   });
}

function touristeslist()
{
   var code="";
   $.ajax({
   url: "command.php?action=touristeslist"
   }).done(function(jsonresponse) {
      jsonobject=$.parseJSON(jsonresponse);
      if (jsonobject.length>0) code='<table class="table table-striped" id="touristestable"><thead><tr><th>Téléphone</th><th>Nationalité</th><th>Nombre de vélo(s)</th><th>Date et heure</th>';
      code=code+'</tr></thead>';
      for (var i=0, len=jsonobject.length; i < len; i++)
         {
         code=code+'<tr><td>'+jsonobject[i]["number"]+'</td><td>'+jsonobject[i]["nation"]+'</td><td>'+jsonobject[i]["nbBikes"]+'</td><td>'+jsonobject[i]["date"]+'</td>';
         }
      if (jsonobject.length>0) code=code+'</table>';
      $('#touristesconsole').html(code);
	  
   });
}

function userstats()
{
   var code="";
   $.ajax({
   url: "command.php?action=userstats"
   }).done(function(jsonresponse) {
      jsonobject=$.parseJSON(jsonresponse);
      if (jsonobject.length>0) code='<h3>Statistiques depuis le début du service</h3><table class="table table-striped" id="userstatstable"><thead><tr><th>Inscriptions</th><th>Locations</th><th>Dépot</th><th>Signalements vélos</th><th>Dépot par administrateur</th></tr></thead>';
      for (var i=0, len=jsonobject.length; i < len; i++)
         {
         code=code+'<tr><td>'+jsonobject[i]["totaluser"]+'</td><td>'+jsonobject[i]["totalloc"]+'</td><td>'+jsonobject[i]["totaldepot"]+'</td><td>'+jsonobject[i]["totalnote"]+'</td><td>'+jsonobject[i]["totalforce"]+'</td></tr>';
         }
      if (jsonobject.length>0) code=code+'</table>';
      $('#reportsconsole').html(code);
      createeditlinks();
   });
}

function usagestats()
{
   var code="";
   $.ajax({
   url: "command.php?action=usagestats"
   }).done(function(jsonresponse) {
      jsonobject=$.parseJSON(jsonresponse);
      if (jsonobject.length>0) code='<h3>Statistiques journalière</h3><table class="table table-striped" id="usagestatstable"><thead><tr><th>Jour</th><th>Actions</th><th>Nombre</th></tr></thead>';
      for (var i=0, len=jsonobject.length; i < len; i++)
         {
         code=code+'<tr><td>'+jsonobject[i]["day"]+'</td><td>'+jsonobject[i]["action"]+'</td><td>'+jsonobject[i]["count"]+'</td></tr>';
         }
      if (jsonobject.length>0) code=code+'</table>';
      $('#reportsconsole').html(code);
   });
}

function monthstats(){
   var code="";
   $.ajax({
   url: "command.php?action=monthstats"
   }).done(function(jsonresponse) {
      jsonobject=$.parseJSON(jsonresponse);
      if (jsonobject.length>0) code='<h3>Statistiques sur les 30 derniers jours</h3><table class="table table-striped" id="usagestatstable"><thead><tr><th>Inscriptions</th><th>Locations</th><th>Dépot</th><th>Dépot par des administrateurs</th><th>Signalements</th></tr></thead>';
      for (var i=0, len=jsonobject.length; i < len; i++)
         {
         code=code+'<tr><td>'+jsonobject[i]["inscription"]+'</td><td>'+jsonobject[i]["mloc"]+'</td><td>'+jsonobject[i]["mdepot"]+'</td><td>'+jsonobject[i]["mforce"]+'</td><td>'+jsonobject[i]["msign"]+'</td></tr>';
         }
      if (jsonobject.length>0) code=code+'</table>';
      $('#reportsconsole').html(code);
   });
}

function standstats(){
   var code="";
   $.ajax({
   url: "command.php?action=standstats"
   }).done(function(jsonresponse) {
      jsonobject=$.parseJSON(jsonresponse);
      if (jsonobject.length>0) code='<h3>Statistiques par stations</h3><table class="table table-striped" id="usagestatstable"><thead><tr><th>Stations</th><th>Locations</th><th>Dépot</th><th>Dépot par des administrateurs</th></tr></thead>';
      for (var i=0, len=jsonobject.length; i < len; i++)
         {
         code=code+'<tr><td>'+jsonobject[i]["placename"]+'</td><td>'+jsonobject[i]["locbystand"]+'</td><td>'+jsonobject[i]["depotbystand"]+'</td><td>'+jsonobject[i]["forcedepbystand"]+'</td></tr>';
         }
      if (jsonobject.length>0) code=code+'</table>';
      $('#reportsconsole').html(code);
   });
}

function createeditlinks()
{
   $('.edituser').each(function () {
      $(this).click(function () { if (window.ga) ga('send', 'event', 'buttons', 'click', 'admin-edituser', $(this).attr('data-userid')); edituser($(this).attr('data-userid')); });
   });
}

function removesignalement(standId)
{
    $.ajax({
   url: "command.php?action=removesignalement&standId="+standId
   }).done(function(jsonresponse) {
      jsonobject=$.parseJSON(jsonresponse);
      handleresponse("standsconsole",jsonobject);
   });
}

function removeuser(userid){
   var txt;
   var r = confirm("Vous êtes sur le point de supprimer un utilisateur ! Pour confirmer appuyez sur OK, si c'était une erreur appuyer sur annuler");
   if (r == true) {
      $.ajax({
      url: "command.php?action=removeuser&userid="+userid
      }).done(function(jsonresponse) {
         jsonobject=$.parseJSON(jsonresponse);
         handleresponse("userconsole",jsonobject);
      });
   } else {
     txt = "You pressed Cancel!";
   }
}

function edituser(userid)
{
   $.ajax({
   url: "command.php?action=edituser&edituserid="+userid
   }).done(function(jsonresponse) {
      jsonobject=$.parseJSON(jsonresponse);
      if (jsonobject)
         {
         $('#userid').val(jsonobject["userid"]);
         $('#username').val(jsonobject["username"]);
         $('#email').val(jsonobject["email"]);
         if ($('#phone')) $('#phone').val(jsonobject["phone"]);
         $('#privileges').val(jsonobject["privileges"]);
         $('#limit').val(jsonobject["limit"]);
         $('#edituser').show();
         $('a[href=#users]').trigger('click');
         }
   });
}

function saveuser()
{
   if (window.ga) ga('send', 'event', 'buttons', 'click', 'admin-saveuser', $('#userid').val());
   var phone="";
   if ($('#phone')) phone="&phone="+$('#phone').val();
   $.ajax({
   url: "command.php?action=saveuser&edituserid="+$('#userid').val()+"&username="+$('#username').val()+"&email="+$('#email').val()+"&privileges="+$('#privileges').val()+"&limit="+$('#limit').val()+phone
   }).done(function(jsonresponse) {
      jsonobject=$.parseJSON(jsonresponse);
      $("#edituser").hide();
      handleresponse("userconsole",jsonobject);
      setTimeout(userlist, 2000);
   });
}

function trips()
{
   if (window.ga) ga('send', 'event', 'bikes', 'trips', $('#adminparam').val());
   $.ajax({
   url: "command.php?action=trips&bikeno="+$('#adminparam').val()
   }).done(function(jsonresponse) {
      jsonobject=$.parseJSON(jsonresponse);
      if (jsonobject.error==1)
         {
         handleresponse(elementid,jsonobject);
         }
      else
         {
         if (jsonobject[0]) // concrete bike requested
            {
            if (polyline!=undefined) map.removeLayer(polyline);
            polyline = L.polyline([[jsonobject[0].latitude*1,jsonobject[0].longitude*1],[jsonobject[1].latitude*1,jsonobject[1].longitude*1]], {color: 'red'}).addTo(map);
            for (var i=2, len=jsonobject.length; i < len; i++)
               {
               if (jsonobject[i].longitude*1 && jsonobject[i].latitude*1)
                  {
                  polyline.addLatLng([jsonobject[i].latitude*1,jsonobject[i].longitude*1]);
                  }
               }
            }
         else // all bikes requested
            {
            var polylines=[];
            for (var bikenumber in jsonobject)
               {
               var bikecolor='#'+('00000'+(Math.random()*16777216<<0).toString(16)).substr(-6);
               polylines[bikenumber] = L.polyline([[jsonobject[bikenumber][0].latitude*1,jsonobject[bikenumber][0].longitude*1],[jsonobject[bikenumber][1].latitude*1,jsonobject[bikenumber][1].longitude*1]], {color: bikecolor}).addTo(map);
               for (var i=2, len=jsonobject[bikenumber].length; i < len; i++)
                  {
                  if (jsonobject[bikenumber][i].longitude*1 && jsonobject[bikenumber][i].latitude*1)
                     {
                     polylines[bikenumber].addLatLng([jsonobject[bikenumber][i].latitude*1,jsonobject[bikenumber][i].longitude*1]);
                     }
                  }
               }
            }

         }
   });
}

function revert()
{
   if (window.ga) ga('send', 'event', 'bikes', 'revert', $('#adminparam').val());
   $.ajax({
   url: "command.php?action=revert&bikeno="+$('#adminparam').val()
   }).done(function(jsonresponse) {
      jsonobject=$.parseJSON(jsonresponse);
      handleresponse("fleetconsole",jsonobject);
   });
}

function fnResetAllFilters() {
    var oSettings = oTable.fnSettings();
    for(iCol = 0; iCol < oSettings.aoPreSearchCols.length; iCol++) {
        oSettings.aoPreSearchCols[ iCol ].sSearch = '';
    }
    oTable.fnDraw();
}