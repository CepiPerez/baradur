// ----------------------------------------------------
// Buscador con botones de limpiar y buscar
// (C) 2021. Matias Perez para New Rol IT
// ----------------------------------------------------
// form = el id del formulario padre 
// (necesario para poder hacer el submit)
//
// valor = el valor que tiene el buscador 
// (si no se viene de una busqueda deberia estar vacio)
//
// NOTA: Es necesario el custom.css para los iconos
// ----------------------------------------------------

class Buscador extends HTMLElement
{
    constructor() {
        super();

        var myid = $(this).attr("id")? $(this).attr("id") : 'buscador';
        var buscarval = $(this).attr("valor") ? $(this).attr("valor") : '';
        var formulario = $(this).attr("form");
        console.log("Inicializando buscador: " + myid + " - valor: " + buscarval);

        this.classList.add("form-group");
        this.setAttribute("id", myid);
    
        var inputtext = document.createElement('input');
        inputtext.classList.add("form-control");
        inputtext.classList.add("text");
        inputtext.setAttribute("placeholder", "Buscar");
        inputtext.setAttribute("name", "buscar");
        inputtext.setAttribute("value", buscarval);
        $('#'+myid).append(inputtext);
    
        var limpiar = document.createElement('a');
        limpiar.classList.add("fa");
        limpiar.classList.add("fa-times-circle");
        limpiar.classList.add("invisible");
        limpiar.setAttribute("id", "limpiar");
        limpiar.setAttribute("href", "");
        $('#'+myid).append(limpiar);
    
        var separador = document.createElement('p');
        separador.classList.add("separator");
        separador.classList.add("invisible");
        separador.innerHTML = "|"
        $('#'+myid).append(separador);
    
        var buscar = document.createElement('a');
        buscar.classList.add("fa");
        buscar.classList.add("fa-search");
        buscar.classList.add("disabled");
        buscar.setAttribute("id", "buscar");
        buscar.setAttribute("href", "");
        $('#'+myid).append(buscar);
    
    
        if ($(inputtext).val()!=="")
        {
            $(limpiar).removeClass("invisible");
            $(separador).removeClass("invisible");
        }
    
        $(limpiar).click(function(e) {
            e.preventDefault()
            $(inputtext).val("");
            $(buscar).addClass("disabled");
            $(limpiar).addClass("invisible");
            $(separador).addClass("invisible");
            $(inputtext).focus();
            if (buscarval!=="") {
                $(inputtext).val("");
                $('#'+formulario).submit();
            }
        });
    
        $(inputtext).on('change keyup paste', function () {
            if ($(inputtext).val()=="" && buscarval=="") {
              $(buscar).addClass("disabled");
              $(limpiar).addClass("invisible");
              $(separador).addClass("invisible");
            } else {
              $(buscar).removeClass("disabled");
              $(limpiar).removeClass("invisible");
              $(separador).removeClass("invisible");
            }
        });
        
        $(buscar).click(function(e) {
            e.preventDefault()
            $('#'+formulario).submit();
        });
    
    }

}
customElements.define('mi-buscador', Buscador);




class Tabla extends HTMLElement
{
    
    constructor() {
        super();
        
        function extraerVariables (text) {
            var res = text.match(/(\$[a-zA-Z0-9_]+)/gi);
            if (res!=null) return $.grep(res, function(n, i) { return res.indexOf(n) == i; });
            else return "";
        }

        function capitalizeString(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }
          

        var myid = $(this).attr("id")? $(this).attr("id") : 'tabla';
        var headers = $(this).attr("headers") ? $(this).attr("headers") : '';
        var columns = $(this).attr("columns") ? $(this).attr("columns") : '';
        var actions = $(this).attr("actions") ? $(this).attr("actions") : '';
        var data = $(this).attr("datos") ? $(this).attr("datos") : '';
        data = JSON.parse(data);
        console.log("Inicializando tabla: " + myid + " - datos: " + data.length);
        //console.log(data)

        if (actions!='')
        {
            actions = "[" + actions.replaceAll("[","{").replaceAll("]","}").replaceAll("'", '"') + "]";
            actions = JSON.parse(actions);
        }

        this.classList.add("table");
        this.classList.add("table-striped");
        this.setAttribute("id", myid);

        if (headers!=='')
        {
            headers = headers.split(";")
            var thead = document.createElement('thead');
            var tr = document.createElement('tr');
            for (var i in headers)
            {
                var th = document.createElement('th');
                if (headers[i].indexOf("(auto)") >= 0) {
                    th.setAttribute("scope", "col w-auto");
                    th.innerHTML = headers[i].replace("(auto)", "");
                } else if (headers[i].indexOf("(right)") >= 0) {
                    th.setAttribute("scope", "col");
                    th.setAttribute("class", "text-right");
                    th.innerHTML = headers[i].replace("(right)", "");
                } else {
                    th.setAttribute("scope", "col");
                    th.innerHTML = headers[i];
                }
                tr.append(th);
                thead.append(tr);
            }
            $('#'+myid).append(thead);
        }

        columns = columns.split(";")
        var capcolumns = [];
        for (var i in columns)
        {
            if (columns[i].indexOf("(capitalize)") >= 0) {
                capcolumns.push( columns[i].replaceAll("(capitalize)","") );
            }
        }
        columns = columns.join(";");
        columns = columns.replaceAll("(capitalize)","");
        columns = columns.split(";")
        var tbody = document.createElement('tbody');
        data.forEach(function(obj) 
        {
            console.log(obj)
            tr = document.createElement('tr');
            if (obj['enabled']==false)
                tr.setAttribute("style", "color:#ff4561;");
            $.each( obj, function( key, value )
            {
                if (jQuery.inArray(key, columns) !== -1)
                {
                    var td = document.createElement('td');
                    if (jQuery.inArray(key, capcolumns) !== -1)
                        td.innerHTML = capitalizeString(value);
                    else
                        td.innerHTML = value;
                    tr.append(td);
                }
            });

            if (actions.length>0)
            {
                var td = document.createElement('td');
                td.classList.add("text-right");
                td.classList.add("no-pointer");
                actions.forEach(function(cobj) 
                {
                    $.each( cobj, function( key, value )
                    {
                        //console.log(value);

                        if (key=="edit")
                        {
                            var link = value['link']
                            var sres = extraerVariables(link); 
                            if (sres) {
                                sres.forEach((x, i) => { link = link.replaceAll(x, obj[x.replaceAll('$','')]) });
                            }
                            var a = document.createElement('a');
                            a.classList.add("fa");
                            a.classList.add("fa-edit");
                            a.classList.add("ml-3");
                            a.setAttribute("href", link);
                            td.append(a);
                        }
                        if (key=="enable")
                        {
                            var link = value['link']
                            var sres = extraerVariables(link); 
                            if (sres) {
                                sres.forEach((x, i) => { link = link.replaceAll(x, obj[x.replaceAll('$','')]) });
                            }
                            var enabled = value['enabled']
                            var sres = extraerVariables(enabled); 
                            if (sres) {
                                sres.forEach((x, i) => { enabled = enabled.replaceAll(x, obj[x.replaceAll('$','')]) });
                            }
                            var a = document.createElement('a');
                            a.classList.add("fa");
                            if (enabled=='true') a.classList.add("fa-lock");
                            else a.classList.add("fa-unlock");
                            a.classList.add("ml-3");
                            a.setAttribute("href", link);
                            td.append(a);
                        }
                        if (key=="delete")
                        {
                            var link = value['link']
                            var sres = extraerVariables(link); 
                            if (sres) {
                                sres.forEach((x, i) => { link = link.replaceAll(x, obj[x.replaceAll('$','')]) });
                            }
                            var submitname = value['submitname']
                            var sres = extraerVariables(submitname); 
                            if (sres) {
                                sres.forEach((x, i) => { submitname = submitname.replaceAll(x, obj[x.replaceAll('$','')]) });
                            }
                            var submitvalue = value['submitvalue']
                            var sres = extraerVariables(submitvalue); 
                            if (sres) {
                                sres.forEach((x, i) => { submitvalue = submitvalue.replaceAll(x, obj[x.replaceAll('$','')]) });
                            }
                            var a = document.createElement('a');
                            a.classList.add("fa");
                            a.classList.add("fa-trash");
                            a.classList.add("ml-3");
                            a.setAttribute("href", link);
                            a.setAttribute("submitname", submitname);
                            a.setAttribute("submitvalue", submitvalue);
                            td.append(a);
                        }
                    });
    
                });
                tr.append(td);
            }
            tbody.append(tr);
        });
        $('#'+myid).append(tbody);


        var form = document.createElement('form');
        form.setAttribute("id", "delete-form");
        form.setAttribute("method", "post");
        form.setAttribute("action", "");
        var input = document.createElement('input');
        input.setAttribute("type", "hidden");
        input.setAttribute("id", "removeitem");
        input.setAttribute("value", "");
        form.append(input);
        this.append(form);

        $('.fa-trash').click(function(e) {
            e.preventDefault()
            window.confirm('Esta seguro que desea eliminar el informe?')?
            input.setAttribute('name', $(this).attr('submitname')) &
            input.setAttribute('value',$(this).attr('submitvalue')) &
            form.setAttribute('action',$(this).attr('href')) &
            form.submit()
            : "";
        });



    }
}
customElements.define('mi-tabla', Tabla);
