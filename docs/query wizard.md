SELECT nokontrak, kdprd, kdloc, pokpby,tglexp, iif(osmdlc<0,1,osmdlc) as osmdlc, iif(osmgnc<0,1,osmgnc) as osmgnc, iif(tgkmdl<0,1,tgkmdl) as tgkmdl, iif(tgkmgn<0,1,tgkmgn) as tgkmgn,
iif(haritgkmdl>haritgkmgn,haritgkmdl, haritgkmgn) as haritgk, IIF(colbaru='',5,colbaru) as colbaru, stsrec, stsacc, ppap, periode FROM TOFLMBEOM
where kdprd<>'30' and stsrec in ('A','N') and periode between '202101' and '202412'



SELECT nokontrak, nocif, nama, kdprd, kdloc, pokpby, gunadeb, tglwo FROM TOFLMB where kdprd<>'30' and stsrec<>'D'
