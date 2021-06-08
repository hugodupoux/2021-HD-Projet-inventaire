DROP TABLE tb_objet;
DROP TABLE tb_motif_suppression;

CREATE TABLE tb_objet(
    obj_id int PRIMARY KEY auto_increment,
    obj_aho varchar(10) NOT NULL UNIQUE,
    obj_annee_entree smallint NOT NULL,
    obj_annee_sortie smallint, 
    obj_fk_motif_suppression int,
    obj_scanne bit NOT NULL,
    obj_nom varchar(200) NOT NULL,
    obj_prix float NOT NULL
);

CREATE TABLE tb_motif_suppression(
    motsup_id int PRIMARY KEY auto_increment,
    motsup_libelle varchar(50) NOT NULL UNIQUE
);

ALTER TABLE tb_objet
ADD CONSTRAINT FK_motif_suppr FOREIGN KEY(obj_fk_motif_suppression) REFERENCES tb_motif_suppression(motsup_id);
