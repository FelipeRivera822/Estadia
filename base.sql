CREATE DATABASE facturacion_db;

USE facturacion_db;

CREATE TABLE facturacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    RFC VARCHAR(20) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    usoCFDI VARCHAR(100) NOT NULL,
    regimenFiscal VARCHAR(100) NOT NULL,
    codigoPostal VARCHAR(10) NOT NULL,
    correo VARCHAR(100) NOT NULL,
    Sucursal VARCHAR(10) NOT NULL,
    numTicket VARCHAR(10) NOT NULL,
    Caja VARCHAR(10) NOT NULL,
    totalCompra DECIMAL(10, 2) NOT NULL,
    fechaCompra DATE NOT NULL
);
