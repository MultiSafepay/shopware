file="/var/run/mysqld/mysqld.sock.lock"
if [ -f "$file" ] ; then
    sudo rm -f "$file"
fi

sudo chown -R mysql:mysql /var/lib/mysql /var/run/mysqld
sudo service mysql start;


/wait-for-it/wait-for-it.sh 127.0.0.1:3306 -- echo "database is up"

mysql -uroot -proot shopware -e "update s_core_shops set secure=1 where host LIKE '%localhost%'"
mysql -uroot -proot shopware -e "update s_core_shops set host='$1.$2' where host LIKE '%localhost%'"
/entrypoint.sh
