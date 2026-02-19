// Modelo Venta.php
public function cuotas()
{
    // Sintaxis: hasMany(ModeloHijo, 'Nombre_FK_en_hijo', 'Mi_PK_Local')
    
    return $this->hasMany(Cuota::class, 'id_venta', 'id');
}
// Modelo Cuota.php
public function venta()
{
    // Sintaxis: belongsTo(ModeloPadre, 'Mi_FK_Local', 'PK_del_padre')
    
    return $this->belongsTo(Venta::class, 'id_venta', 'id');
}