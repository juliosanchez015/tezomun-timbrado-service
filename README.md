<p align="center">
    <img src="https://tesoreria-neza.gob.mx/panel/imags/logohorizontal.png" width="100%" alt="Big" />
</p>

# README #

![](panel/imags/logo_neza.png)

>Composer laravel 

>Paquete para el timbrado de facturas Comercio Digital

    Paquete para la integración con el servicio de timbrado de Comercio Digital

****
>Como subir cambios

>   commit git

    git add .
    git commit -am "feat:.."

>   agregar el tag

    git tag v1.*.*

>   subir el tag 
    
    git push origin v1.1.8

>   subir a main
        
    git push origin master
****
>   Realizar lo con git flow
######   
    craer un branch
    git flow feature start <branch>
    git flow feature finish <branch>
    
######
    crear un release
    git flow release start <tag>
    git flow release finish <tag>
    
    No olvides añadir las tags con 
    git push --tags

>   Instalar composer paquete personalizado

    composer config repositories.timbrado vcs git@bitbucket.org:Rosenrot015/tezomun-timbrado-service.git
    **** dev-main o la version que va v1.1.10
    composer require Rosenrot015/tezomun-timbrado-service:dev-main
    php artisan vendor:publish --tag=config