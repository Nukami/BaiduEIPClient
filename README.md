# BaiduEIPClient
EIP client written in PHP using API provided by Baidu Elastic IP  
Hope it can help you to understand how http request signed in Baidu Cloud

# File list
+ algorithms.php : Algorithms to generate token, signature, etc.
+ bccclient.php : BCC client to get BCC instances list and BCC instance info
+ chars.php : chars array for generating eip name
+ conf.php : Setting Access Key and Secret Key
+ eipclient.php : EIP client, it provides several functions includes getEipList, purchaseNewEip, etc.
+ rebind.php : An sample shows the usage of eipclient.php from unbind an old EIP to bind a new EIP to BCC instance

# Bugs
### Wrong EIP list
getEipList may returns some wrong information of EIPs, the last time I met this bug for example, the JSON string that getEipList returned included an EIP instance with status "creating" for a long time, no matter I bound or unbound this EIP instance, even though I deleted it.

# License
*NO LICENSE*  
Use as you wish  
Nukami, Jan/2019