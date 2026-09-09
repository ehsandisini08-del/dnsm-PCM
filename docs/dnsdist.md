# DNSDist Load Balancing & DDoS Protection Guide

DNSDist is a highly customizable, scriptable DNS load balancer designed for ISPs and high-traffic nameserver infrastructures.

---

## 1. DNSDist Architecture & Role

```
Internet Clients
      │  (UDP/TCP 53)
      ▼
┌─────────────────────────────────┐
│ DNSDist Load Balancer           │
│  - Dynamic Query Rate Limiting  │
│  - Packet Cache (RAM)           │
│  - Health Checking & Failover   │
└────────────────┬────────────────┘
                 │
      ┌──────────┴──────────┐
      ▼                     ▼
┌──────────────┐      ┌──────────────┐
│ PowerDNS NS1 │      │ PowerDNS NS2 │
│ 10.0.0.11:53 │      │ 10.0.0.12:53 │
└──────────────┘      └──────────────┘
```

---

## 2. Installation (Ubuntu 24.04 LTS)

```bash
sudo apt update
sudo apt install -y dnsdist
```

---

## 3. Configuration (`/etc/dnsdist/dnsdist.conf`)

```lua
-- Bind DNSDist to public IP addresses
setLocal("0.0.0.0:53", { reusePort=true })
addLocal("[::]:53", { reusePort=true })

-- Define PowerDNS Authoritative Backends
newServer({
    address="10.0.0.11:53",
    name="ns1",
    checkName="example.com",
    checkType="A",
    mustResolve=false,
    weight=1
})

newServer({
    address="10.0.0.12:53",
    name="ns2",
    checkName="example.com",
    checkType="A",
    mustResolve=false,
    weight=1
})

-- Load Balancing Strategy
setServerPolicy(roundrobin)

-- Memory Packet Cache (Caches hot authoritative answers)
pc = newPacketCache(100000, {maxTTL=86400, minTTL=0})
getPool(""):setPacketCache(pc)

-- DDoS Rate Limiting Protection (Max 100 queries per second per client IP)
addAction(MaxQPSIPRule(100, 32, 64), DropAction())

-- ACL: Allow global DNS queries
addACL("0.0.0.0/0")
addACL("::/0")

-- Web Console (Optional)
webserver("127.0.0.1:8083")
setWebserverConfig({password="YourSecureWebPassword", apiKey="YourSecureApiKey"})
```

---

## 4. Starting DNSDist

```bash
sudo systemctl restart dnsdist
sudo systemctl enable dnsdist

# Test DNS query through DNSDist
dig @127.0.0.1 example.com A
```
