sudo myisam_ftdump -c /var/lib/mysql/fioriech65618/taxa_search 3 | sort -rn | head -n 1000 | awk '{print $3}' > /home/caiofior/Scaricati/fioriechiavi/test/words.txt