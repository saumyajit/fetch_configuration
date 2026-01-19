# 📦 Zabbix Module – Fetch Host Configuration

A Zabbix frontend module that allows administrators to **view, extract, and export host configuration data** directly from the Zabbix Web UI.  
This module makes it easy to audit, document, back up, or compare host configurations without writing Zabbix API scripts.

---

## ✨ Features

✔ **Fetch configuration for any host**  
✔ **Export host configuration in multiple formats:**  
- **CSV** – ideal for spreadsheets, auditing, and comparisons  
- **HTML** – readable reports for documentation  
- **JSON** – perfect for automation, backups, and DevOps workflows  

✔ **Integrates directly into the Zabbix frontend**  
✔ **No external tools required**  
✔ **Lightweight and simple to deploy**

---

## 📋 What Information Can Be Exported?

The module retrieves key host configuration items, including:

- Host name & visible name  
- Status (enabled/disabled)  
- Host groups  
- Linked templates  
- Interfaces (Agent / SNMP / IPMI / JMX)  
- Host macros  
- Inventory data  
- Proxy assignment  
- Description
- Tags
- Other host metadata

This makes it useful for audits, compliance checks, debugging, and migration.

---

## 🚀 Installation

1. **Clone or download** this repository:
   ```bash
   git clone https://github.com/saumyajit/zabbix_module_fetch_host_configuration
   
2. **Copy the module folder into your Zabbix frontend modules directory.**  
   For most installations this is:

   - `/usr/share/zabbix/modules/`  
   - or `/usr/share/zabbix/modules/ui/`  

   After copying, the module should look like:

   - `/usr/share/zabbix/modules/zabbix_module_fetch_host_configuration/`  
   - or `/usr/share/zabbix/modules/ui/zabbix_module_fetch_host_configuration/`

3. **Ensure correct file permissions for the Zabbix web user:**

   **RHEL / CentOS:**  
   - `chown -R apache:apache /usr/share/zabbix/modules/zabbix_module_fetch_host_configuration`

   **Debian / Ubuntu:**  
   - `chown -R www-data:www-data /usr/share/zabbix/modules/zabbix_module_fetch_host_configuration`

4. **Log into the Zabbix web interface as an administrator.**

5. Navigate to:  
**Administration → Modules**

6. Click **Scan directory**.

7. Locate the module and click **Enable**.

---

## 🖥️ Usage

Once enabled:

1. A new menu entry will appear in the Zabbix UI:  
**Fetch Host Configuration**

2. Select a host or choose to fetch all hosts.

3. Choose an export option:
- **Download CSV**
- **Download HTML**
- **Download JSON**

4. Save or open the exported host configuration file.

---

## 📚 Use Cases

- 🔍 **Audits & Compliance Reporting**  
- 🗄️ **Backup of host configuration**  
- 🔄 **Migrating hosts between Zabbix environments**  
- 📑 **Generating documentation**  
- 🧰 **Debugging configuration issues**  
- ⚙️ **DevOps automation using JSON exports**

---

## 🛠 Compatibility

The module is designed for Zabbix frontend versions:

- **Zabbix 7.x**

(You need to modify API methods to make this work for below versions)
- **Zabbix 5.x**
- **Zabbix 6.x**
---

## 📄 License

This project is licensed under the **GNU GPL v3.0**.  
See the `LICENSE` file for details.

---

## 🤝 Contributing

Contributions, issues, and feature requests are welcome!  
Feel free to open an issue or submit a pull request.


🙌 Acknowledgments

Thanks to the Zabbix community for official module development guidelines and inspiration.

---

## 🗺️ Roadmap

This module is actively being improved. Below is the planned development roadmap:

### **📌 Current Version**
- Fetch configuration for a single host  
- Export in CSV, HTML, and JSON formats  

### **🚧 In Development**
- UI enhancements and performance improvements

### **🔮 Upcoming Features**
- **Fetch configuration for multiple selected hosts**  
  Export configuration for several hosts at once in your preferred format.

- **Fetch configuration for entire host groups**  
  Select a host group and retrieve/export all configurations inside that group.

- **Bulk export options**  
  One-click export for “All hosts” across the entire environment.

---
